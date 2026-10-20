<?php
// Hardened session cookie (set before session_start)
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/db.php';

$dataFile = __DIR__ . '/data/submissions.json';
$message  = '';
$error    = '';

// ---- Security settings ----
const MIN_FILL_SECONDS   = 20;   // humans cannot fill a 5-page form faster than this
const MAX_FORM_AGE       = 7200; // form token valid for 2 hours
const RATE_LIMIT_MAX     = 5;    // max submissions per IP ...
const RATE_LIMIT_WINDOW  = 3600; // ... per hour

// CSRF token (one per session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash message after redirect (Post/Redirect/Get)
if (!empty($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

/** Simple per-IP rate limiter stored in a file (flock-protected). Returns true if allowed. */
function rateLimitAllow($ip, $max, $window) {
    $file = __DIR__ . '/data/ratelimit.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // fail open rather than block real users
    flock($fp, LOCK_EX);
    $raw  = stream_get_contents($fp);
    $all  = json_decode($raw ?: '[]', true) ?: [];
    $now  = time();
    foreach ($all as $k => $times) {
        $all[$k] = array_values(array_filter($times, fn($t) => $t > $now - $window));
        if (!$all[$k]) unset($all[$k]);
    }
    $key = hash('sha256', $ip);
    $hits = $all[$key] ?? [];
    $allowed = count($hits) < $max;
    if ($allowed) {
        $hits[] = $now;
        $all[$key] = $hits;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($all));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $allowed;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['csrf_token'] ?? '';
    $ts      = (int)($_POST['form_ts'] ?? 0);
    $elapsed = time() - $ts;
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        $error = 'Security check failed (invalid or expired form token). Please reload the page and try again.';
    } elseif (!empty($_POST['website'])) {
        // Honeypot field filled in -> bot. Pretend success, store nothing.
        $_SESSION['flash'] = 'Application form and health screening submitted successfully!';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } elseif ($ts <= 0 || $elapsed < MIN_FILL_SECONDS || $elapsed > MAX_FORM_AGE) {
        http_response_code(400);
        $error = 'The form was submitted too quickly or has expired. Please reload the page and try again.';
    } elseif (!rateLimitAllow($ip, RATE_LIMIT_MAX, RATE_LIMIT_WINDOW)) {
        http_response_code(429);
        $error = 'Too many submissions from your network. Please try again later.';
    } else {
        // Strip security/internal fields and cap field sizes
        $clean = $_POST;
        unset($clean['csrf_token'], $clean['form_ts'], $clean['website']);
        array_walk_recursive($clean, function (&$v) {
            if (is_string($v)) $v = mb_substr(trim($v), 0, 2000);
        });

        $dbSaved = saveFullApplication($clean);

        $record = [
            'submitted_at' => date('Y-m-d H:i:s'),
            'form_type'    => 'afhmc_full_application',
            'data'         => $clean
        ];

        $fp = @fopen($dataFile, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            $existing = json_decode(stream_get_contents($fp) ?: '[]', true) ?: [];
            $existing[] = $record;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // One-time token: rotate after a successful submission, then redirect (prevents resubmit on refresh)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['flash'] = 'Application form and health screening submitted successfully!';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Air Force Health Management Centre - AFHMC</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#d7d7d7;color:#111;font-family:Arial,Helvetica,sans-serif}
.topbar{position:sticky;top:0;z-index:50;background:#111827;color:#fff;padding:10px 16px;display:flex;gap:8px;align-items:center;justify-content:center;box-shadow:0 2px 10px #0003}
.topbar button{border:1px solid #ffffff55;background:#ffffff12;color:#fff;padding:8px 13px;border-radius:5px;cursor:pointer;font-weight:700}
.topbar button:hover,.topbar button.active{background:#fff;color:#111827}
.notice{max-width:1120px;margin:14px auto 0;padding:10px 14px;background:#e9f7df;border:1px solid #8ab66b;color:#24551b;border-radius:5px;font-weight:bold;text-align:center}
.page{width:210mm;min-height:297mm;margin:16px auto;background:#ffff80;padding:20mm 22mm;position:relative;box-shadow:0 2px 14px #0004;overflow:hidden}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12mm}
.header img.logo{width:31mm;height:auto;object-fit:contain}
.header img.bird{width:35mm;height:auto;object-fit:contain}
.header-title{text-align:center;font-family:"Times New Roman",serif;font-weight:700;font-size:20px;line-height:1.25;flex:1}
.header-title small{font-size:18px}
h1,h2,h3{margin:0}.doc-title{font-size:18px;font-weight:700;margin:5mm 0 6mm}.section-title{font-weight:700;margin:4mm 0 3mm}
label{font-size:14px}.line-row{display:flex;align-items:center;gap:8px;margin:7px 0}.line-label{min-width:118px;font-weight:400}.line-input{border:0;border-bottom:2px dotted #111;background:transparent;outline:none;font-size:14px;padding:2px 4px;flex:1;min-width:0}.short{max-width:260px}.medium{max-width:430px}.date-small{width:55px;border:0;border-bottom:2px dotted #111;background:transparent}
.check-group{display:flex;gap:45px;align-items:center;flex-wrap:wrap;margin:12px 0}.check-item{display:flex;gap:9px;align-items:center}.check-item input[type=checkbox],.check-item input[type=radio]{width:18px;height:18px;accent-color:#111}
.para{font-size:14px;line-height:1.45}.italic{font-style:italic}.bold{font-weight:700}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:10px}.official{border-top:2px dashed #111;margin-top:10px;padding-top:9px}.official-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 30px}.actions{text-align:center;margin:20px 0}.submit{background:#111827;color:white;border:0;border-radius:5px;padding:12px 28px;font-size:15px;font-weight:700;cursor:pointer}.reset{background:#fff;border:1px solid #111827;border-radius:5px;padding:9px 18px;cursor:pointer}
.form-table{width:100%;border-collapse:collapse;font-size:14px;margin:5mm 0 10mm}.form-table td{border:1px solid #111;padding:5px}.form-table td:first-child,.form-table td:nth-child(3){width:18%}
.question{display:grid;grid-template-columns:28px 1fr 34px 34px;gap:8px;align-items:start;margin:5px 0;font-size:14px;line-height:1.3}.question .qcheck{width:18px;height:18px}.yn-head{display:grid;grid-template-columns:1fr 34px 34px;gap:8px;text-align:center;font-weight:700;margin:6px 0}.subheading{font-weight:700;margin-top:8mm;margin-bottom:5px}.inline-checks{display:flex;gap:14px;align-items:center;flex-wrap:wrap;font-size:14px}.inline-checks label{display:flex;align-items:center;gap:5px}.inline-checks input{width:16px;height:16px}
.rules{font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.38}.rules-title{text-align:center;font-family:"Times New Roman",serif;text-decoration:underline;font-size:19px;font-weight:700;margin:6mm 0}.rule{display:grid;grid-template-columns:45px 1fr;gap:0;margin:11px 0}.rule .roman{font-weight:400}.indent{margin-left:55px}.facilities{margin-left:55px;line-height:1.4}.rates-title{font-size:18px;text-decoration:underline;font-weight:700;margin-top:20px}.rates{width:100%;border-collapse:collapse;margin-top:10px;font-family:"Times New Roman",serif}.rates th,.rates td{border:1px solid #111;padding:8px;vertical-align:middle}.rates th{font-weight:700;text-align:center}.rates td:first-child{font-weight:700}.small{font-size:11px}
.footer-nav{max-width:1120px;margin:15px auto 30px;display:flex;justify-content:center;gap:8px}.footer-nav button{padding:9px 14px;border:1px solid #555;background:#fff;border-radius:4px;cursor:pointer}
@media(max-width:900px){.page{width:96vw;min-height:auto;padding:6vw;background:#ffff80}.header img.logo{width:22vw}.header img.bird{width:24vw}.header-title{font-size:17px}.topbar{overflow:auto;justify-content:flex-start}.official-grid{grid-template-columns:1fr}.two-col{grid-template-columns:1fr}.question{grid-template-columns:25px 1fr 30px 30px}}
@media print{
  body{background:#fff}
  .topbar,.notice,.footer-nav,.footer-actions,.actions{display:none!important}
  .page{display:block!important;margin:0;width:210mm;min-height:297mm;box-shadow:none;page-break-after:always}
  .page:last-of-type{page-break-after:auto}
}
</style>
</head>
<body>
<div class="topbar">
  <button type="button" onclick="showPage(1)" id="nav1">Membership Application</button>
  <button type="button" onclick="showPage(2)" id="nav2">Health Screening</button>
  <button type="button" onclick="showPage(3)" id="nav3">Rules 1</button>
  <button type="button" onclick="showPage(4)" id="nav4">Rules 2</button>
  <button type="button" onclick="showPage(5)" id="nav5">Rules & Rates</button>
</div>
<?php if ($message): ?><div class="notice"><?=e($message)?></div><?php endif; ?>
<?php if ($error): ?><div class="notice" style="background:#fde8e8;border-color:#d66;color:#8a1f1f"><?=e($error)?></div><?php endif; ?>

<!-- SINGLE UNIFIED FORM FOR THE ENTIRE WEB DOCUMENT -->
<form method="post" id="afhmc_master_form">
<input type="hidden" name="form_type" value="afhmc_full">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<input type="hidden" name="form_ts" value="<?=time()?>">
<!-- Honeypot: hidden from humans, bots tend to fill it -->
<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">
  <label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
</div>

<!-- PAGE 1 -->
<section class="page" id="page1">
<div class="header"><img class="logo" src="assets/slaf-logo.png"><div class="header-title">Air Force<br>Health Management Centre<br><small>AFHMC</small></div><img class="bird" src="assets/afhmc-bird.png"></div>
<div class="doc-title">MEMBERSHIP APPLICATION FORM FOR RAFOA MEMBERS</div>
<div class="section-title">TYPE OF MEMBERSHIP</div>
<div class="check-group">
<label class="check-item"><input type="checkbox" name="membership_type" value="FULL"> FULL</label>
<label class="check-item"><input type="checkbox" name="membership_type" value="GYM&amp;COURT"> GYM&amp; COURT</label>
<label class="check-item"><input type="checkbox" name="membership_type" value="COURT ONLY"> COURT ONLY</label>
</div>
<p class="para italic bold">Please fill in blockcapital letters.</p>
<div class="check-group"><b> TITLE:</b>
<label class="check-item"><input type="checkbox" name="title" value="MR"> MR</label>
<label class="check-item"><input type="checkbox" name="title" value="MRS"> MRS</label>
<label class="check-item"><input type="checkbox" name="title" value="MS"> MS</label>
<label class="check-item"><input type="checkbox" name="title" value="DR"> DR</label>
<label class="check-item"><input type="checkbox" name="title" value="OTHER"> OTHER :</label></div>
<div class="line-row"><span class="line-label">RANK :</span><input class="line-input short" name="rank"></div>
<div class="line-row"><span class="line-label">FIRST NAME :</span><input class="line-input short" name="first_name" id="p1_first_name"><span>LAST NAME:</span><input class="line-input medium" name="last_name" id="p1_last_name"></div>
<div class="line-row"><span class="line-label">ADDRESS</span><span>:</span><input class="line-input" name="address"></div>
<div class="line-row"><span class="line-label">OCCUPATION/<br>DESIGNATION</span><span>:</span><input class="line-input short" name="occupation"></div>
<div class="line-row"><span class="line-label">NATIONALITY</span><span>:</span><input class="line-input short" name="nationality"></div>
<div class="line-row"><span class="line-label">OFFICIAL ADDRESS</span><span>:</span><input class="line-input" name="official_address"></div>
<div class="line-row"><span class="line-label">PASSPORT/NIC NO</span><span>:</span><input class="line-input short" name="passport_nic"><span>DATE OF BIRTH: D/</span><input class="date-small" name="dob_d" id="p1_dob_d"><span>M/</span><input class="date-small" name="dob_m" id="p1_dob_m"><span>Y/</span><input class="date-small" name="dob_y" id="p1_dob_y"></div>
<div class="line-row"><span class="line-label">TELEPHONE</span><span>:</span><input class="line-input short" name="telephone"><span>MOBILE NO :</span><input class="line-input short" name="mobile" id="p1_mobile"></div>
<div class="line-row"><span class="line-label">E-MAIL</span><span>:</span><input class="line-input short" name="email"></div>
<p class="para italic">I have seen, read and understood the Rules and Regulations of the Air Force Health Management Centre Gym &amp; Spa and hereby agree undertake to abide by the said Rules and Regulations.</p>
<p class="para italic bold">Please draw cheque in favour of ‘Service Institute Fund, Sri Lanka Air Force Station, Colombo”.</p>
<div class="line-row"><input class="line-input short" name="signature"><span></span></div><div class="para">SIGNATURE OF APPLICANT</div>
<div class="line-row" style="margin-top:20px"><span>RECOMMENDATION OF PRESIDENT RAFOA</span><span>:</span><input class="line-input medium" name="recommendation"><span>(seal)</span></div>
<div class="official"><b>FOR OFFICIAL USE ONLY ;</b>
<div class="official-grid">
<div>Category <input class="line-input short" name="category"></div><div>Date of Issue <input class="line-input short" name="date_issue"></div>
<div>Date of Expiry <input class="line-input short" name="date_expiry"></div><div></div>
<div>Date Received <input class="line-input short" name="date_received"></div><div>RECEIPT NO <input class="line-input short" name="receipt_no"></div>
<div>AMOUNT RAID <input class="line-input short" name="amount_paid"></div><div>PAYMENT METHOD : CASH/CHEQUE<br>CARD NUMBER: <input class="line-input short" name="card_number"></div>
</div><p><b>APPROVED/NOT APPROVED</b></p><div class="two-col"><div><input class="line-input short" name="commander"><br>COMMANDER OF THE AIR FORCE</div><div><input class="line-input short" name="approval_date"><br>DATE</div></div>
</div>
</section>

<!-- PAGE 2 -->
<section class="page" id="page2" style="display:none">
<div class="header"><img class="logo" src="assets/slaf-logo.png"><div class="header-title">Air Force<br>Health Management Centre<br><small>AFHMC</small></div><img class="bird" src="assets/afhmc-bird.png"></div>
<h2 style="text-align:center;font-size:18px;margin-bottom:7mm">Health Screening Form</h2>
<table class="form-table">
  <tr>
    <td>First Name</td><td>: <input class="line-input short" id="p2_first_name"></td>
    <td>Last Name</td><td>: <input class="line-input short" id="p2_last_name"></td>
  </tr>
  <tr>
    <td>Gender</td><td>: <input class="line-input short" name="gender"></td>
    <td>Age</td><td>: <input class="line-input short" name="age"></td>
  </tr>
  <tr>
    <td>Date of Birth</td><td>: <input class="line-input short" name="dob" id="p2_dob"></td>
    <td>Mobile Number</td><td>: <input class="line-input short" id="p2_mobile"></td>
  </tr>
</table>
<div class="yn-head"><span>Cardiovascular : <span style="font-weight:400">Risk factors (Please tick appropriate box)</span></span><span>YES</span><span>NO</span></div>
<?php
$questions=[
'Has a physician ever told you that have high blood pressure?','Do you have diabetes?','Do you have a heart condition, heart murmur or accelerated heart rate?','Does anyone in your immediate family have any heart problems prior to the age of 55 ?','Have you ever experienced chest pain?','Do you have any knowledge of your cholesterol level?','Do you smoke?','Have you ever experienced abnormal dizziness or fainting attacks?','Have you experienced unaccustomed shortness of breath with mild exertion?','Are you currently talking any medication?'];
foreach($questions as $i=>$q): $n=$i+1; ?><div class="question"><span><?=$n?>.</span><span><?=e($q)?></span><input class="qcheck" type="radio" name="cardio_<?=$n?>" value="YES"><input class="qcheck" type="radio" name="cardio_<?=$n?>" value="NO"></div><?php endforeach; ?>
<p class="para italic">It yes, what medications are you taking and are you aware of any side effect of this medication?</p><input class="line-input" style="width:100%" name="medications">
<div class="subheading">Orthopedic : <span style="font-weight:400">Risk factor (please tick the appropriate)</span></div>
<div class="para">1. &nbsp;&nbsp;&nbsp;Do you have any back problem? <span class="inline-checks" style="display:inline-flex"><label><input type="checkbox" name="back_problem[]" value="Neck"> Neck</label><label><input type="checkbox" name="back_problem[]" value="Middle"> Middle</label><label><input type="checkbox" name="back_problem[]" value="Lower"> Lower</label></span></div>
<div class="para" style="margin-top:7px">2. &nbsp;&nbsp;&nbsp;Have you had any problems with your?</div><div class="inline-checks" style="margin-left:30px"><label><input type="checkbox" name="orthopedic[]" value="Shoulder"> Shoulder</label><label><input type="checkbox" name="orthopedic[]" value="Elbow"> Elbow</label><label><input type="checkbox" name="orthopedic[]" value="Wrist"> Wrist</label><label><input type="checkbox" name="orthopedic[]" value="Hand"> Hand</label><label><input type="checkbox" name="orthopedic[]" value="Knee"> Knee</label><label><input type="checkbox" name="orthopedic[]" value="Hip"> Hip</label><label><input type="checkbox" name="orthopedic[]" value="Ankle"> Ankle</label><label><input type="checkbox" name="orthopedic[]" value="Foot"> Foot</label></div>
<div class="para" style="margin-top:7px">3. &nbsp;&nbsp;&nbsp;Have you ever had a hernia? <span class="inline-checks" style="display:inline-flex"><label>Yes <input type="checkbox" name="hernia" value="Yes"></label><label>No <input type="checkbox" name="hernia" value="No"></label></span></div>
<div class="para" style="margin-top:7px">4. &nbsp;&nbsp;&nbsp;Have you had any surgeries with the past five years? <span class="inline-checks" style="display:inline-flex"><label>Yes <input type="checkbox" name="surgeries" value="Yes"></label><label>No <input type="checkbox" name="surgeries" value="No"></label></span></div>
<div class="para italic">If yes, please give details: <input class="line-input" name="surgery_details" style="width:72%"></div>
<p class="para italic">If above 1-4 are ticked as ‘YES’ please submit a report/letter from your doctor (This information is needed prior to membership?</p>
<div class="subheading">Lifestyle Questions:</div>
<div class="para">1. &nbsp;&nbsp;&nbsp;What is your daily stress level? <span class="inline-checks" style="display:inline-flex"><label>Low <input type="checkbox" name="stress" value="Low"></label><label>Medium <input type="checkbox" name="stress" value="Medium"></label><label>High <input type="checkbox" name="stress" value="High"></label></span></div>
<div class="para" style="margin-top:7px">2. &nbsp;&nbsp;&nbsp;What is your current activity level? <span class="inline-checks" style="display:inline-flex"><label>Very active <input type="checkbox" name="activity" value="Very active"></label><label>Occasional experience <input type="checkbox" name="activity" value="Occasional experience"></label><label>Never Exercise <input type="checkbox" name="activity" value="Never Exercise"></label></span></div>
<div class="para" style="margin-top:7px">3. &nbsp;&nbsp;&nbsp;Date of last physical examination <input class="line-input" name="last_exam" style="width:55%"></div>
<div class="two-col" style="margin-top:40px"><div>Signature : <input class="line-input" name="health_signature"></div><div>Date:<input class="line-input" name="health_date"></div></div>
</section>

<!-- PAGE 3 -->
<section class="page rules" id="page3" style="display:none">
<div class="header"><img class="logo" src="assets/slaf-logo.png"><div></div><img class="bird" src="assets/afhmc-bird.png"></div>
<div class="rules-title">RULES AND CODE OF CONDUCT<br>AIR FORCE HEALTH MANAGEMENT CENTRE (AFHMC)</div>
<div class="rule"><span>a.</span><span>The Club is exclusively for the use of AFHMC Members.</span></div>
<div class="rule"><span>b.</span><span>Membership Types<br><br><div style="margin-left:0">Full <span style="display:inline-block;width:110px"></span>: Health center, gymnasium, courts<br>Gym and Court <span style="display:inline-block;width:65px"></span>: Gymnasium and courts<br>Court only <span style="display:inline-block;width:87px"></span>: Court only<br><br>All memberships are non-transferable.</div></span></div>
<div class="rule"><span>c.</span><span>Operating Hours <span style="display:inline-block;width:80px"></span>: Daily <span style="display:inline-block;width:75px"></span>- 0500 hrs to 2100 hrs</span></div>
<div class="rule"><span>d.</span><span>Management reserves the right to prohibit Members into the AFHMC in case of an emergency (as determined by the management) or if in the option of management it is in the best interest of the AFHMC to do so.</span></div>
<div class="rule"><span>e.</span><span>Membership Form:<br><br><div class="indent">i.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Potential Members should complete the application form and submit it to the management along with two (2) passport size photographs.</div><br><div class="indent">ii.&nbsp;&nbsp;&nbsp;&nbsp;Any applicant shall be accepted or denied membership at the sole discretion of the management of the AFHMC.</div><br><div class="indent">iii.&nbsp;&nbsp;&nbsp;In the event the application is accepted by the management, such applicant will become a Member once the required membership fee/s are paid in full. Upon such payment a membership card will be issued.</div><br><div class="indent">iv.&nbsp;&nbsp;&nbsp;The membership fee, upon being paid to the Club, shall not be refundable.</div><br><div class="indent">v.&nbsp;&nbsp;&nbsp;&nbsp;Should any information stated in the membership form change, the management should be informed of the same, in writing, immediately.</div><br><div class="indent">vi.&nbsp;&nbsp;&nbsp;The membership card is not transferable.</div><br><div class="indent">vii.&nbsp;&nbsp;In case of a membership card being lost or stolen, the Member should inform the management immediately in writing. A replacement shall be obtained on payment of Rs. 500/= within 20 days thereof.</div></span></div>
</section>

<!-- PAGE 4 -->
<section class="page rules" id="page4" style="display:none">
<div class="header"><img class="logo" src="assets/slaf-logo.png"><div></div><img class="bird" src="assets/afhmc-bird.png"></div>
<div class="rule"><span>f.</span><span>Facilities<br><br>Air Force Health Management Centre (AFHMC) Members are entitled to use the following facilities as per their membership category:<br><br><div class="facilities">Gymnasium<br>Steam and sauna<br>Jacuzzi<br>Hot and Cold bath<br>Locker rooms<br>Badminton Court<br>Squash Court<br>Basketball Court<br>Health Center</div></span></div>
<div class="rule"><span>g.</span><span>Etiquette<br><br><div class="indent">i.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Members must maintain good behavior at all times at the AFHMC and elsewhere within AFHMC building and premises.</div><br><div class="indent">ii.&nbsp;&nbsp;&nbsp;&nbsp;Sports clothing, socks, and appropriate sports footwear should be worn when using the gymnasium and courts.</div><br><div class="indent">iii.&nbsp;&nbsp;&nbsp;Members should respect the rights of fellow users at all times and refrain from using abusive language, shouting and disruptive behavior.</div><br><div class="indent">iv.&nbsp;&nbsp;&nbsp;No personal music of sound systems is permitted, unless used with headphones.</div><br><div class="indent">v.&nbsp;&nbsp;&nbsp;&nbsp;Members are prohibited from bringing into the club any food and / or beverage.</div></span></div>
<div class="rule"><span>h.</span><span>General<br><br><div class="indent">i.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Members are advised not to leave any valuable in or about the Club. The management will not be responsible for any losses.</div><br><div class="indent">ii.&nbsp;&nbsp;&nbsp;&nbsp;Locker keys must be returned to the reception on completion of usage Club facilities.</div><br><div class="indent">iii.&nbsp;&nbsp;&nbsp;Members shall not use the Club for illegal purpose or bring into the Club any goods of an explosive, dangerous or offensive nature or which may cause nuisance to the AFHMC or any other Members and shall indemnify and hold harmless the AFHMC from any damage, liability or loss caused by any such action.</div><br><div class="indent">iv.&nbsp;&nbsp;&nbsp;Any loss or damage to AFHMC property owing / attributable to any act or omission of the Member or Permitted Persons shall be recovered from such person and until the recovery of the same the management may at its discretion prohibit such person from entering the AFHMC.</div></span></div>
</section>

<!-- PAGE 5 -->
<section class="page rules" id="page5" style="display:none">
<div class="rule"><span>v.</span><span>Management will not be responsible for any injury, accident or illness caused to the Members at the AFHMC.</span></div>
<div class="rule"><span>vi.</span><span>Members with any medical history should produce any available medical reports prior to commencing an exercise programme.</span></div>
<div class="rule"><span>vii.</span><span>Private coaching is strictly prohibited. (No personal trainers from outside will be allowed in to the AFHMC). Instructors on duty may be asked for any necessary assistance.</span></div>
<div class="rule"><span>viii.</span><span>Children under 18 are not permitted to use the gymnasium.</span></div>
<div class="rule"><span>ix.</span><span>Smoking is prohibited in all areas of the Center.</span></div>
<div class="rule"><span>x.</span><span>Pets are not permitted into the Center.</span></div>
<div class="rule"><span>xi</span><span>Cycling, roller skating, skate boarding will not be permitted in and around the Center nor anywhere else at the Gym and Spa building and premises.</span></div>
<div class="rule"><span>j.</span><span>The AFHMC shall endeavor to provide the Members with the best service possible, however the AFHMC shall not be responsible for failure to perform its obligations due to natural and man made disasters or such other event beyond the control of the AFHMC.</span></div>
<div class="rule"><span>k.</span><span>The management of the Gym and Spa may terminate the membership of any person who is found to be in breach of the rules and regulations of Club as set out herein and as amended or introduced from time to time. Upon such termination the membership fee paid for the unutilized term of the membership will be reimbursed on a pro rata basis subsequent to the dedication of any payment due and owing to the Club.</span></div>
<p style="margin-top:22px">The management of the AFHMC reserves the right to add, delete or amend any of the above rules and regulations.</p>
<div class="rates-title">Membership rates</div>
<table class="rates"><thead><tr><th>Membership/Category</th><th>RAFOA Members</th><th>Spouse of RAFOA Members</th></tr></thead><tbody>
<tr><td>Full Membership <span style="font-weight:400">(Spa/ Gymnasium/ Court)</span></td><td>Rs.12,000/=<br><span class="small">(Rs.1000/= per month)</span></td><td>Rs.12,000/=<br><span class="small">(Rs.1000/= per month)</span></td></tr>
<tr><td>Gymnasium/Court Membership</td><td>Rs.9,000/=<br><span class="small">(Rs.750/= per month)</span></td><td>Rs.9,000/=<br><span class="small">(Rs.750/= per month)</span></td></tr>
<tr><td>Court only Membership</td><td>Rs.6,000/=<br><span class="small">(Rs.500/= per month)</span></td><td>Rs.6,000/=<br><span class="small">(Rs.500/= per month)</span></td></tr>
</tbody></table>

<div class="actions" style="margin-top:25px;">
  <button type="submit" class="submit">Submit Complete Application</button>
</div>

<!-- PRINT BUTTON ON LAST PAGE -->
<div class="footer-actions" style="text-align:center;margin:25px 0 10px;">
  <button type="button" class="print-btn" onclick="window.print()" style="background:#111827;color:white;border:1px solid #111827;border-radius:5px;padding:12px 28px;font-size:16px;font-weight:700;cursor:pointer;">Print Full Document (All 5 Pages)</button>
</div>
</section>

</form>

<div class="footer-nav">
  <button type="button" onclick="showPage(1)">1</button>
  <button type="button" onclick="showPage(2)">2</button>
  <button type="button" onclick="showPage(3)">3</button>
  <button type="button" onclick="showPage(4)">4</button>
  <button type="button" onclick="showPage(5)">5</button>
</div>

<script>
function showPage(n){
  for(let i=1;i<=5;i++){
    document.getElementById('page'+i).style.display=(i===n?'block':'none');
    document.getElementById('nav'+i).classList.toggle('active',i===n);
  }
  window.scrollTo({top:0,behavior:'smooth'});
}

// Auto-sync duplicated fields from Page 1 to Page 2 for user convenience
function syncFields() {
  const p1FirstName = document.getElementById('p1_first_name');
  const p2FirstName = document.getElementById('p2_first_name');
  const p1LastName  = document.getElementById('p1_last_name');
  const p2LastName  = document.getElementById('p2_last_name');
  const p1Mobile    = document.getElementById('p1_mobile');
  const p2Mobile    = document.getElementById('p2_mobile');
  const p1Dd        = document.getElementById('p1_dob_d');
  const p1Dm        = document.getElementById('p1_dob_m');
  const p1Dy        = document.getElementById('p1_dob_y');
  const p2Dob       = document.getElementById('p2_dob');

  if (p1FirstName && p2FirstName) {
    p1FirstName.addEventListener('input', () => { p2FirstName.value = p1FirstName.value; });
  }
  if (p1LastName && p2LastName) {
    p1LastName.addEventListener('input', () => { p2LastName.value = p1LastName.value; });
  }
  if (p1Mobile && p2Mobile) {
    p1Mobile.addEventListener('input', () => { p2Mobile.value = p1Mobile.value; });
  }
  const updateDob = () => {
    if (p2Dob) p2Dob.value = `${p1Dd?.value || ''}/${p1Dm?.value || ''}/${p1Dy?.value || ''}`;
  };
  if (p1Dd) p1Dd.addEventListener('input', updateDob);
  if (p1Dm) p1Dm.addEventListener('input', updateDob);
  if (p1Dy) p1Dy.addEventListener('input', updateDob);
}
document.addEventListener('DOMContentLoaded', syncFields);

showPage(1);
</script>
</body>
</html>
