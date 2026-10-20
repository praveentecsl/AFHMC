<?php
session_start();
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db.php';
$pdo = getDbConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid Application ID.");
}

$stmt = $pdo->prepare("SELECT * FROM afhmc_applications WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();

if (!$d) {
    die("Application not found in database.");
}

function e($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Application #<?=e($d['id'])?> - AFHMC</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#d7d7d7;color:#111;font-family:Arial,Helvetica,sans-serif}
.topbar{position:sticky;top:0;z-index:50;background:#111827;color:#fff;padding:10px 16px;display:flex;gap:8px;align-items:center;justify-content:center;box-shadow:0 2px 10px #0003}
.topbar button,.topbar a{border:1px solid #ffffff55;background:#ffffff12;color:#fff;padding:8px 13px;border-radius:5px;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block}
.topbar button:hover,.topbar button.active,.topbar a:hover{background:#fff;color:#111827}
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
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:10px}.official{border-top:2px dashed #111;margin-top:10px;padding-top:9px}.official-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 30px}
.form-table{width:100%;border-collapse:collapse;font-size:14px;margin:5mm 0 10mm}.form-table td{border:1px solid #111;padding:5px}.form-table td:first-child,.form-table td:nth-child(3){width:18%}
.question{display:grid;grid-template-columns:28px 1fr 34px 34px;gap:8px;align-items:start;margin:5px 0;font-size:14px;line-height:1.3}.question .qcheck{width:18px;height:18px}.yn-head{display:grid;grid-template-columns:1fr 34px 34px;gap:8px;text-align:center;font-weight:700;margin:6px 0}.subheading{font-weight:700;margin-top:8mm;margin-bottom:5px}.inline-checks{display:flex;gap:14px;align-items:center;flex-wrap:wrap;font-size:14px}.inline-checks label{display:flex;align-items:center;gap:5px}.inline-checks input{width:16px;height:16px}
.rules{font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.38}.rules-title{text-align:center;font-family:"Times New Roman",serif;text-decoration:underline;font-size:19px;font-weight:700;margin:6mm 0}.rule{display:grid;grid-template-columns:45px 1fr;gap:0;margin:11px 0}.rule .roman{font-weight:400}.indent{margin-left:55px}.facilities{margin-left:55px;line-height:1.4}.rates-title{font-size:18px;text-decoration:underline;font-weight:700;margin-top:20px}.rates{width:100%;border-collapse:collapse;margin-top:10px;font-family:"Times New Roman",serif}.rates th,.rates td{border:1px solid #111;padding:8px;vertical-align:middle}.rates th{font-weight:700;text-align:center}.rates td:first-child{font-weight:700}.small{font-size:11px}
.footer-nav{max-width:1120px;margin:15px auto 30px;display:flex;justify-content:center;gap:8px}.footer-nav button{padding:9px 14px;border:1px solid #555;background:#fff;border-radius:4px;cursor:pointer}
@media(max-width:900px){.page{width:96vw;min-height:auto;padding:6vw;background:#ffff80}.header img.logo{width:22vw}.header img.bird{width:24vw}.header-title{font-size:17px}.topbar{overflow:auto;justify-content:flex-start}.official-grid{grid-template-columns:1fr}.two-col{grid-template-columns:1fr}.question{grid-template-columns:25px 1fr 30px 30px}}
@media print{
  body{background:#fff}
  .topbar,.footer-nav,.footer-actions{display:none!important}
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
  <a href="dashboard.php" style="background:#111;margin-left:auto;">&larr; Back to Dashboard</a>
  <button type="button" onclick="window.print()" style="background:#2563eb;">Print Record</button>
</div>

<!-- PAGE 1 -->
<section class="page" id="page1">
<div class="header"><img class="logo" src="../assets/slaf-logo.png"><div class="header-title">Air Force<br>Health Management Centre<br><small>AFHMC</small></div><img class="bird" src="../assets/afhmc-bird.png"></div>
<div class="doc-title">MEMBERSHIP APPLICATION FORM FOR RAFOA MEMBERS (ID: <?=e($d['id'])?> | Submitted: <?=e($d['submitted_at'])?>)</div>
<div class="section-title">TYPE OF MEMBERSHIP</div>
<div class="check-group">
<label class="check-item"><input type="checkbox" <?=($d['membership_type']==='FULL'?'checked':'')?> disabled> FULL</label>
<label class="check-item"><input type="checkbox" <?=($d['membership_type']==='GYM&COURT'?'checked':'')?> disabled> GYM&amp; COURT</label>
<label class="check-item"><input type="checkbox" <?=($d['membership_type']==='COURT ONLY'?'checked':'')?> disabled> COURT ONLY</label>
</div>
<p class="para italic bold">Please fill in blockcapital letters.</p>
<div class="check-group"><b> TITLE:</b>
<label class="check-item"><input type="checkbox" <?=($d['title']==='MR'?'checked':'')?> disabled> MR</label>
<label class="check-item"><input type="checkbox" <?=($d['title']==='MRS'?'checked':'')?> disabled> MRS</label>
<label class="check-item"><input type="checkbox" <?=($d['title']==='MS'?'checked':'')?> disabled> MS</label>
<label class="check-item"><input type="checkbox" <?=($d['title']==='DR'?'checked':'')?> disabled> DR</label>
<label class="check-item"><input type="checkbox" <?=($d['title']==='OTHER'?'checked':'')?> disabled> OTHER :</label></div>
<div class="line-row"><span class="line-label">RANK :</span><input class="line-input short" value="<?=e($d['rank'])?>" readonly></div>
<div class="line-row"><span class="line-label">FIRST NAME :</span><input class="line-input short" value="<?=e($d['first_name'])?>" readonly><span>LAST NAME:</span><input class="line-input medium" value="<?=e($d['last_name'])?>" readonly></div>
<div class="line-row"><span class="line-label">ADDRESS</span><span>:</span><input class="line-input" value="<?=e($d['address'])?>" readonly></div>
<div class="line-row"><span class="line-label">OCCUPATION/<br>DESIGNATION</span><span>:</span><input class="line-input short" value="<?=e($d['occupation'])?>" readonly></div>
<div class="line-row"><span class="line-label">NATIONALITY</span><span>:</span><input class="line-input short" value="<?=e($d['nationality'])?>" readonly></div>
<div class="line-row"><span class="line-label">OFFICIAL ADDRESS</span><span>:</span><input class="line-input" value="<?=e($d['official_address'])?>" readonly></div>
<div class="line-row"><span class="line-label">PASSPORT/NIC NO</span><span>:</span><input class="line-input short" value="<?=e($d['passport_nic'])?>" readonly><span>DATE OF BIRTH:</span><input class="line-input medium" value="<?=e($d['dob'])?>" readonly></div>
<div class="line-row"><span class="line-label">TELEPHONE</span><span>:</span><input class="line-input short" value="<?=e($d['telephone'])?>" readonly><span>MOBILE NO :</span><input class="line-input short" value="<?=e($d['mobile'])?>" readonly></div>
<div class="line-row"><span class="line-label">E-MAIL</span><span>:</span><input class="line-input short" value="<?=e($d['email'])?>" readonly></div>
<p class="para italic">I have seen, read and understood the Rules and Regulations of the Air Force Health Management Centre Gym &amp; Spa and hereby agree undertake to abide by the said Rules and Regulations.</p>
<p class="para italic bold">Please draw cheque in favour of ‘Service Institute Fund, Sri Lanka Air Force Station, Colombo”.</p>
<div class="line-row"><input class="line-input short" value="<?=e($d['signature'])?>" readonly><span></span></div><div class="para">SIGNATURE OF APPLICANT</div>
<div class="line-row" style="margin-top:20px"><span>RECOMMENDATION OF PRESIDENT RAFOA</span><span>:</span><input class="line-input medium" value="<?=e($d['recommendation'])?>" readonly><span>(seal)</span></div>
<div class="official"><b>FOR OFFICIAL USE ONLY ;</b>
<div class="official-grid">
<div>Category <input class="line-input short" value="<?=e($d['category'])?>" readonly></div><div>Date of Issue <input class="line-input short" value="<?=e($d['date_issue'])?>" readonly></div>
<div>Date of Expiry <input class="line-input short" value="<?=e($d['date_expiry'])?>" readonly></div><div></div>
<div>Date Received <input class="line-input short" value="<?=e($d['date_received'])?>" readonly></div><div>RECEIPT NO <input class="line-input short" value="<?=e($d['receipt_no'])?>" readonly></div>
<div>AMOUNT RAID <input class="line-input short" value="<?=e($d['amount_paid'])?>" readonly></div><div>PAYMENT METHOD : CASH/CHEQUE<br>CARD NUMBER: <input class="line-input short" value="<?=e($d['card_number'])?>" readonly></div>
</div><p><b>APPROVED/NOT APPROVED</b></p><div class="two-col"><div><input class="line-input short" value="<?=e($d['commander'])?>" readonly><br>COMMANDER OF THE AIR FORCE</div><div><input class="line-input short" value="<?=e($d['approval_date'])?>" readonly><br>DATE</div></div>
</div>
</section>

<!-- PAGE 2 -->
<section class="page" id="page2" style="display:none">
<div class="header"><img class="logo" src="../assets/slaf-logo.png"><div class="header-title">Air Force<br>Health Management Centre<br><small>AFHMC</small></div><img class="bird" src="../assets/afhmc-bird.png"></div>
<h2 style="text-align:center;font-size:18px;margin-bottom:7mm">Health Screening Form</h2>
<table class="form-table">
  <tr>
    <td>First Name</td><td>: <input class="line-input short" value="<?=e($d['first_name'])?>" readonly></td>
    <td>Last Name</td><td>: <input class="line-input short" value="<?=e($d['last_name'])?>" readonly></td>
  </tr>
  <tr>
    <td>Gender</td><td>: <input class="line-input short" value="<?=e($d['gender'])?>" readonly></td>
    <td>Age</td><td>: <input class="line-input short" value="<?=e($d['age'])?>" readonly></td>
  </tr>
  <tr>
    <td>Date of Birth</td><td>: <input class="line-input short" value="<?=e($d['dob'])?>" readonly></td>
    <td>Mobile Number</td><td>: <input class="line-input short" value="<?=e($d['mobile'])?>" readonly></td>
  </tr>
</table>
<div class="yn-head"><span>Cardiovascular : <span style="font-weight:400">Risk factors (Please tick appropriate box)</span></span><span>YES</span><span>NO</span></div>
<?php
$questions=[
'Has a physician ever told you that have high blood pressure?','Do you have diabetes?','Do you have a heart condition, heart murmur or accelerated heart rate?','Does anyone in your immediate family have any heart problems prior to the age of 55 ?','Have you ever experienced chest pain?','Do you have any knowledge of your cholesterol level?','Do you smoke?','Have you ever experienced abnormal dizziness or fainting attacks?','Have you experienced unaccustomed shortness of breath with mild exertion?','Are you currently talking any medication?'];
foreach($questions as $i=>$q): 
    $n=$i+1; 
    $val = $d["cardio_$n"] ?? '';
?><div class="question"><span><?=$n?>.</span><span><?=e($q)?></span><input class="qcheck" type="radio" <?=($val==='YES'?'checked':'')?> disabled><input class="qcheck" type="radio" <?=($val==='NO'?'checked':'')?> disabled></div><?php endforeach; ?>
<p class="para italic">If yes, what medications are you taking and are you aware of any side effect of this medication?</p><input class="line-input" style="width:100%" value="<?=e($d['medications'])?>" readonly>
<div class="subheading">Orthopedic : <span style="font-weight:400">Risk factor (please tick the appropriate)</span></div>
<div class="para">1. &nbsp;&nbsp;&nbsp;Do you have any back problem? <input class="line-input" value="<?=e($d['back_problem'])?>" readonly></div>
<div class="para" style="margin-top:7px">2. &nbsp;&nbsp;&nbsp;Have you had any problems with your? <input class="line-input" value="<?=e($d['orthopedic'])?>" readonly></div>
<div class="para" style="margin-top:7px">3. &nbsp;&nbsp;&nbsp;Have you ever had a hernia? <input class="line-input" value="<?=e($d['hernia'])?>" readonly></div>
<div class="para" style="margin-top:7px">4. &nbsp;&nbsp;&nbsp;Have you had any surgeries with the past five years? <input class="line-input" value="<?=e($d['surgeries'])?>" readonly></div>
<div class="para italic">If yes, please give details: <input class="line-input" value="<?=e($d['surgery_details'])?>" readonly style="width:72%"></div>
<p class="para italic">If above 1-4 are ticked as ‘YES’ please submit a report/letter from your doctor (This information is needed prior to membership?)</p>
<div class="subheading">Lifestyle Questions:</div>
<div class="para">1. &nbsp;&nbsp;&nbsp;What is your daily stress level? <input class="line-input" value="<?=e($d['stress'])?>" readonly></div>
<div class="para" style="margin-top:7px">2. &nbsp;&nbsp;&nbsp;What is your current activity level? <input class="line-input" value="<?=e($d['activity'])?>" readonly></div>
<div class="para" style="margin-top:7px">3. &nbsp;&nbsp;&nbsp;Date of last physical examination <input class="line-input" value="<?=e($d['last_exam'])?>" readonly></div>
<div class="two-col" style="margin-top:40px"><div>Signature : <input class="line-input" value="<?=e($d['health_signature'])?>" readonly></div><div>Date:<input class="line-input" value="<?=e($d['health_date'])?>" readonly></div></div>
</section>

<!-- PAGE 3 -->
<section class="page rules" id="page3" style="display:none">
<div class="header"><img class="logo" src="../assets/slaf-logo.png"><div></div><img class="bird" src="../assets/afhmc-bird.png"></div>
<div class="rules-title">RULES AND CODE OF CONDUCT<br>AIR FORCE HEALTH MANAGEMENT CENTRE (AFHMC)</div>
<div class="rule"><span>a.</span><span>The Club is exclusively for the use of AFHMC Members.</span></div>
<div class="rule"><span>b.</span><span>Membership Types<br><br><div style="margin-left:0">Full <span style="display:inline-block;width:110px"></span>: Health center, gymnasium, courts<br>Gym and Court <span style="display:inline-block;width:65px"></span>: Gymnasium and courts<br>Court only <span style="display:inline-block;width:87px"></span>: Court only<br><br>All memberships are non-transferable.</div></span></div>
<div class="rule"><span>c.</span><span>Operating Hours <span style="display:inline-block;width:80px"></span>: Daily <span style="display:inline-block;width:75px"></span>- 0500 hrs to 2100 hrs</span></div>
<div class="rule"><span>d.</span><span>Management reserves the right to prohibit Members into the AFHMC in case of an emergency (as determined by the management) or if in the option of management it is in the best interest of the AFHMC to do so.</span></div>
<div class="rule"><span>e.</span><span>Membership Form:<br><br><div class="indent">i.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Potential Members should complete the application form and submit it to the management along with two (2) passport size photographs.</div><br><div class="indent">ii.&nbsp;&nbsp;&nbsp;&nbsp;Any applicant shall be accepted or denied membership at the sole discretion of the management of the AFHMC.</div><br><div class="indent">iii.&nbsp;&nbsp;&nbsp;In the event the application is accepted by the management, such applicant will become a Member once the required membership fee/s are paid in full. Upon such payment a membership card will be issued.</div><br><div class="indent">iv.&nbsp;&nbsp;&nbsp;The membership fee, upon being paid to the Club, shall not be refundable.</div><br><div class="indent">v.&nbsp;&nbsp;&nbsp;&nbsp;Should any information stated in the membership form change, the management should be informed of the same, in writing, immediately.</div><br><div class="indent">vi.&nbsp;&nbsp;&nbsp;The membership card is not transferable.</div><br><div class="indent">vii.&nbsp;&nbsp;In case of a membership card being lost or stolen, the Member should inform the management immediately in writing. A replacement shall be obtained on payment of Rs. 500/= within 20 days thereof.</div></span></div>
</section>

<!-- PAGE 4 -->
<section class="page rules" id="page4" style="display:none">
<div class="header"><img class="logo" src="../assets/slaf-logo.png"><div></div><img class="bird" src="../assets/afhmc-bird.png"></div>
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
</section>

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
showPage(1);
</script>
</body>
</html>