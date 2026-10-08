<?php
// public/index.php - Landing page for Nuahn Seasonal Hub
require_once("../config/init.php");
include_once("../includes/header.php");

// Read-only figures for the hero (display only).
$openJobs  = nu_count($pdo, "SELECT COUNT(*) FROM jobs WHERE status = 'posted'");
$providers = nu_count($pdo, "SELECT COUNT(*) FROM users WHERE role = 'provider'");
$clients   = nu_count($pdo, "SELECT COUNT(*) FROM users WHERE role = 'client'");
$latest = [];
try {
    $latest = $pdo->query("SELECT jobs.id, jobs.title, jobs.payment_amount, jobs.location_address, jobs.location_lat, jobs.location_lng, jobs.created_at, users.name AS client_name
                           FROM jobs JOIN users ON jobs.client_id = users.id
                           WHERE jobs.status = 'posted' ORDER BY jobs.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $latest = []; }
if (!$latest) {
    $latest = [
        ['title' => 'Harvest helpers needed', 'client_name' => 'Green Valley Farms', 'payment_amount' => 120, 'location_address' => 'Accra, Greater Accra', 'created_at' => date('Y-m-d H:i:s')],
        ['title' => 'Holiday event staff', 'client_name' => 'Coastline Events', 'payment_amount' => 85, 'location_address' => 'Tema, Greater Accra', 'created_at' => date('Y-m-d H:i:s')],
        ['title' => 'Garden clean-up', 'client_name' => 'Ama Owusu', 'payment_amount' => 40, 'location_address' => 'East Legon, Accra', 'created_at' => date('Y-m-d H:i:s')],
    ];
}
$findUrl = nu_url('public/jobs.php');
$postUrl = isRole('client') ? nu_url('public/jobs.php#post') : nu_url('public/register_client.php');
?>

<section class="hero">
  <div class="hero__aurora" aria-hidden="true"><span></span><span></span><span></span></div>
  <div class="wrap hero__grid">
    <div>
      <span class="pill-badge enter"><b>New</b> Seasonal work, simplified</span>
      <h1 class="enter" style="--i:1">Trusted help for <span class="grad-text">every season.</span></h1>
      <p class="hero__lead enter" style="--i:2">Nuahn Seasonal Hub connects people who need short-term help — harvests, holidays, events and busy periods — with reliable local providers. Post a job in minutes or find flexible work near you.</p>
      <div class="hero__ctas enter" style="--i:3">
        <a href="<?= nu_e($findUrl) ?>" class="btn btn-primary btn-lg"><?= nu_icon('search') ?> Find seasonal jobs</a>
        <a href="<?= nu_e($postUrl) ?>" class="btn btn-glass btn-lg"><?= nu_icon('plus-lg') ?> Post a job</a>
      </div>
      <div class="trust enter" style="--i:4">
        <div><div class="trust__num" data-count="<?= (int) $openJobs ?>"><?= (int) $openJobs ?></div><div class="trust__label">Open jobs</div></div>
        <div><div class="trust__num" data-count="<?= (int) $providers ?>"><?= (int) $providers ?></div><div class="trust__label">Providers</div></div>
        <div><div class="trust__num" data-count="<?= (int) $clients ?>"><?= (int) $clients ?></div><div class="trust__label">Clients</div></div>
      </div>
    </div>

    <div class="hero__visual" aria-label="Latest jobs">
<?php foreach ($latest as $i => $job): ?>
      <a class="float-card enter" style="--i:<?= $i + 2 ?>" href="<?= nu_e($findUrl . (isset($job['id']) ? '#job' . (int) $job['id'] : '')) ?>">
        <div class="job-card__top">
          <div class="job-card__logo"><?= nu_e(nu_initials($job['client_name'])) ?></div>
          <div><h3 class="job-card__title"><?= nu_e($job['title']) ?></h3><p class="job-card__by"><?= nu_e($job['client_name']) ?></p></div>
        </div>
        <div class="job-card__meta mb-4"><span><?= nu_icon('geo-alt-fill') ?><?= nu_e(nu_place($job)) ?></span><span><?= nu_icon('clock') ?><?= nu_e(nu_ago($job['created_at'])) ?></span></div>
        <div class="float-card__foot">
          <span class="ping">Hiring now</span>
          <span class="job-card__pay"><?= nu_money($job['payment_amount']) ?></span>
        </div>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="how">
  <div class="wrap how">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><?= nu_icon('compass') ?> How it works</span>
      <h2>From posting to paid, in three steps</h2>
      <p>One simple flow for both sides of the marketplace.</p>
    </div>
    <div class="seg-tabs" role="radiogroup" aria-label="Show steps for" data-reveal>
      <input type="radio" name="how" id="how-c" checked><label for="how-c">For clients</label>
      <input type="radio" name="how" id="how-p"><label for="how-p">For providers</label>
      <span class="seg-tabs__thumb" aria-hidden="true"></span>
    </div>
    <div class="steps steps--c">
      <div class="card step" data-reveal style="--i:0"><div class="step__n">1</div><h3>Post your job</h3><p>Describe the task, drop a pin on the map, add a photo and set a fair fixed pay.</p></div>
      <div class="card step" data-reveal style="--i:1"><div class="step__n">2</div><h3>Review applicants</h3><p>Providers nearby apply in a tap. Compare them and approve the right person.</p></div>
      <div class="card step" data-reveal style="--i:2"><div class="step__n">3</div><h3>Get it done</h3><p>Track status from posted to filled to closed, with a full activity trail.</p></div>
    </div>
    <div class="steps steps--p">
      <div class="card step"><div class="step__n">1</div><h3>Create your profile</h3><p>Sign up as a provider in under a minute and set your details.</p></div>
      <div class="card step"><div class="step__n">2</div><h3>Discover jobs nearby</h3><p>Search by keyword or location, browse the map and save jobs for later.</p></div>
      <div class="card step"><div class="step__n">3</div><h3>Apply and earn</h3><p>Apply instantly and follow every application from pending to approved.</p></div>
    </div>
  </div>
</section>

<section class="section section--tint" id="features">
  <div class="wrap">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><?= nu_icon('stars') ?> Why Nuahn</span>
      <h2>Built for trust, speed and peace of mind</h2>
      <p>Everything clients and providers need to work together, without the back-and-forth.</p>
    </div>
    <div class="bento">
      <div class="card feature feature--navy bento--wide" data-reveal>
        <div class="tile__icon"><?= nu_icon('geo-alt-fill') ?></div>
        <h3>Location-based matching</h3>
        <p>Every job carries a map pin, so providers find work close to home and clients reach people who can actually show up.</p>
        <span class="feature__art" aria-hidden="true"></span><?= nu_icon('geo-alt-fill', 'feature__pin') ?>
      </div>
      <div class="card feature" data-reveal style="--i:1">
        <div class="tile__icon"><?= nu_icon('patch-check-fill') ?></div>
        <h3>Verified profiles</h3>
        <p>Clients and providers are verified accounts with clear roles and history.</p>
      </div>
      <div class="card feature" data-reveal style="--i:0">
        <div class="tile__icon tile__icon--navy"><?= nu_icon('cash-coin') ?></div>
        <h3>Fair, transparent pay</h3>
        <p>Fixed pay is shown up front on every job. No surprises.</p>
      </div>
      <div class="card feature" data-reveal style="--i:1">
        <div class="tile__icon"><?= nu_icon('shield-check') ?></div>
        <h3>Compliance built in</h3>
        <p>Every action is logged and monitored for fairness and reliability.</p>
      </div>
      <div class="card feature" data-reveal style="--i:2">
        <div class="tile__icon tile__icon--navy"><?= nu_icon('calendar3') ?></div>
        <h3>Work on your schedule</h3>
        <p>Providers choose jobs that fit their skills, time and season.</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-band" data-reveal>
      <h2>Ready to get started?</h2>
      <p>Join Nuahn Seasonal Hub today and make seasonal work simple, secure and rewarding.</p>
      <div class="hero__ctas">
        <a href="<?= nu_e(nu_url('public/register.php')) ?>" class="btn btn-light btn-lg">Create free account <?= nu_icon('arrow-right') ?></a>
        <a href="<?= nu_e($findUrl) ?>" class="btn btn-glass btn-lg">Browse jobs</a>
      </div>
    </div>
  </div>
</section>

<?php include_once("../includes/footer.php"); ?>
