<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/coming_soon.php: branded Coming Soon splash for the public domain (launch end of November 2026).
// UI ONLY: the "Notify me" form stores nothing (see docs/UI-DATA-HOOKUP.md for the endpoint to connect).
// Standalone on purpose: no database, no session. includes/gate.php shows it for every page request on the
// public domain, and it also works on any host at /public/coming_soon.php so the team can preview it.
require_once __DIR__ . '/../includes/ui.php';

$host     = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$isPublic = in_array($host, ['nuahnseasonalhub.com', 'www.nuahnseasonalhub.com'], true);
// Only the public domain may be indexed; every other host (temporary address, staging) gets noindex.
$noindex  = !$isPublic;
$thanks   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$bad      = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$thanks;
// Nothing is stored or sent: the address is dropped as soon as this response is built.

$svcs = nu_fixture('services');
$live = array_filter($svcs, function ($s) { return ($s['status'] ?? 'live') === 'live'; });
// Launch order: Winter Snow Removal first.
uksort($live, function ($a, $b) { $o = ['snow-removal' => 0, 'mobile-mechanics' => 1]; return ($o[$a] ?? 9) <=> ($o[$b] ?? 9); });
$soon = array_filter($svcs, function ($s) { return ($s['status'] ?? 'live') !== 'live'; });
$blurb = [
    'snow-removal'     => 'Driveways, walkways and steps cleared by vetted local pros, with snowfall updates and a clear, fair price up front.',
    'mobile-mechanics' => 'Roadside help, maintenance and repairs at your door. Get a quote from a vetted independent mechanic before any work starts.',
];
$u = function ($p) { return nu_url($p); };
$season = nu_season();
$pal = nu_season_palette($season);
$ph = $u('public/assets/img/photos/');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    if ($noindex) { header('X-Robots-Tag: noindex, nofollow'); }
}
$form = function ($id) use ($thanks, $bad) {
    ob_start(); ?>
<form class="nf" method="post" action="<?= nu_e(nu_url('public/coming_soon.php')) ?>#<?= $id ?>" novalidate data-notify id="<?= $id ?>">
  <div class="nf__row">
    <label class="vh" for="<?= $id ?>-e">Email address</label>
    <input class="nf__in" id="<?= $id ?>-e" name="email" type="email" inputmode="email" autocomplete="email" placeholder="Your email address" required<?= $bad ? ' aria-invalid="true"' : '' ?>>
    <button class="nf__btn" type="submit"><svg class="i" aria-hidden="true"><use href="#bell"/></svg><span>Notify me</span></button>
  </div>
  <p class="nf__hint" data-hint><?= $bad ? 'Please enter a valid email address.' : 'One email when we open. No spam, ever.' ?></p>
  <p class="nf__ok" role="status" data-ok <?= $thanks ? '' : 'hidden' ?>><svg class="i" aria-hidden="true"><use href="#check-circle-fill"/></svg><span><b>Thanks, we’ll be in touch.</b> We’ll let you know the moment Nuahn opens.</span></p>
</form>
<?php return ob_get_clean(); };
?><!DOCTYPE html>
<html lang="en-CA" data-season="<?= $season ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Nuahn Seasonal Hub | Coming soon: trusted local pros for every Canadian season</title>
<meta name="description" content="Nuahn is a trusted marketplace connecting homeowners with vetted local independent pros for Canadian seasonal care. Winter snow removal and mobile mechanics launch first, end of November 2026.">
<?php if ($noindex): ?><meta name="robots" content="noindex">
<?php endif; ?>
<meta name="theme-color" content="<?= $pal['navy'] ?>">
<meta property="og:title" content="Nuahn Seasonal Hub: coming soon">
<meta property="og:description" content="Trusted local independent pros for Canadian seasonal care. Launching end of November 2026.">
<meta property="og:type" content="website">
<link rel="icon" href="<?= nu_asset('public/assets/img/favicon.ico') ?>" sizes="any">
<link rel="icon" type="image/png" sizes="48x48" href="<?= nu_asset('public/assets/img/favicon-48.png') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= nu_asset('public/assets/img/favicon-32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= nu_asset('public/assets/img/favicon-16.png') ?>">
<link rel="manifest" href="<?= nu_asset('public/assets/manifest.webmanifest') ?>">
<link rel="apple-touch-icon" href="<?= nu_asset('public/assets/img/icon-180.png') ?>">
<link rel="preload" href="<?= $u('public/assets/fonts/outfit-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= $u('public/assets/fonts/jakarta-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:"Jakarta";src:url("<?= $u('public/assets/fonts/jakarta-latin.woff2') ?>") format("woff2");font-weight:200 800;font-display:swap}
@font-face{font-family:"Outfit";src:url("<?= $u('public/assets/fonts/outfit-latin.woff2') ?>") format("woff2");font-weight:500 700;font-display:swap}
@font-face{font-family:"Jakarta Fallback";src:local("Arial");size-adjust:104%;ascent-override:93%;descent-override:22%;line-gap-override:0%}
:root{--teal:#11706D;--teal-d:#0B5A57;--pine:#0A4D4A;--pine-d:#062A28;--ink:#13201F;--ink2:#36443F;--muted:#5D6B67;--line:#E1E9E6;--bg:#F2F6F5;--tint:#EDF6F4;--tint2:#DCEFEC;--mint:#9FD3CD;
--font:"Jakarta","Jakarta Fallback",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;--serif:"Outfit",system-ui,Arial,sans-serif;--ease:cubic-bezier(.2,.7,.2,1)}
/* season palette: one brand colour per season, deeper shades of the same hue for text and dark panels */
:root{--season:<?= $pal['brand'] ?>;--teal:<?= $pal['text'] ?>;--teal-d:<?= $pal['deep'] ?>;--pine:<?= $pal['deep'] ?>;--pine-d:<?= $pal['night'] ?>;--tint:<?= $pal['tint'] ?>;--tint2:<?= $pal['tint2'] ?>;--mint:<?= $pal['soft'] ?>;--night-rgb:<?= implode(',', array_map('hexdec', str_split(ltrim($pal['night'], '#'), 2))) ?>}
h1,.sec__h,.card__t{font-weight:600!important;letter-spacing:-.015em}
.nf__btn{background:var(--season)!important;color:#13201F!important}.nf__btn:hover{filter:brightness(.94)}
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}
body{margin:0;font-family:var(--font);color:var(--ink);background:var(--bg);line-height:1.5;-webkit-font-smoothing:antialiased;overflow-x:hidden}
img,picture{display:block;max-width:100%}
a{color:inherit}
h1,h2,h3,p{margin:0}
.vh{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.i{width:1.1em;height:1.1em;fill:currentColor;flex:none}
:focus-visible{outline:3px solid #fff;outline-offset:2px;box-shadow:0 0 0 5px var(--teal)}
.wrap{width:min(1120px,100% - 40px);margin-inline:auto}
/* logo */
.brandlink{display:inline-flex;text-decoration:none;color:#fff}.logo{display:inline-flex;align-items:center;gap:.5em}.logo img{display:block;flex:none;height:auto}.logo__text{font-weight:800;letter-spacing:-.025em;line-height:1.1;color:inherit}footer .brandlink{color:var(--ink)}
/* hero */
.hero{position:relative;isolation:isolate;color:#fff;min-height:100vh;min-height:100svh;display:flex;flex-direction:column;background:var(--pine-d);overflow:hidden}
.hero__bg,.hero__bg img{position:absolute;inset:0;width:100%;height:100%}
.hero__bg{z-index:-3}
.hero__bg img{object-fit:cover;object-position:62% 50%;transform:scale(1.04);animation:kb 22s var(--ease) both alternate infinite}
.hero::before{content:"";position:absolute;inset:0;z-index:-2;background:linear-gradient(180deg,rgba(var(--night-rgb),.62) 0%,rgba(var(--night-rgb),.38) 36%,rgba(var(--night-rgb),.88) 100%),linear-gradient(90deg,rgba(var(--night-rgb),.6),rgba(var(--night-rgb),0) 72%)}
.snow{position:absolute;inset:-10% 0 0;z-index:-1;pointer-events:none;opacity:.8;
background-image:radial-gradient(2px 2px at 20px 30px,#fff,transparent),radial-gradient(2px 2px at 90px 120px,rgba(255,255,255,.85),transparent),radial-gradient(3px 3px at 160px 60px,rgba(255,255,255,.9),transparent),radial-gradient(1.5px 1.5px at 230px 170px,#fff,transparent),radial-gradient(2.5px 2.5px at 300px 20px,rgba(255,255,255,.8),transparent);
background-size:340px 240px;animation:snow 16s linear infinite}
.snow--b{background-size:520px 380px;opacity:.5;animation-duration:26s;animation-direction:normal;transform:translateX(80px)}
.bar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:calc(18px + env(safe-area-inset-top)) 0 12px}
.pill{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:.78rem;font-weight:700;letter-spacing:.02em;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.pill__s{display:none}.pill .dot{width:8px;height:8px;border-radius:50%;background:#7BE0A0;box-shadow:0 0 0 0 rgba(123,224,160,.6);animation:pulse 2.4s ease-out infinite}
.hero__main{flex:1;display:flex;align-items:center;padding:20px 0 64px}
.hero__copy{max-width:660px}
.eyebrow{font-size:.78rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--mint);margin-bottom:14px}
h1{font-family:var(--serif);font-weight:400;font-size:clamp(2.5rem,1.3rem + 5.6vw,4.9rem);line-height:1.04;letter-spacing:-.01em;text-wrap:balance}
h1 em{font-style:normal;color:var(--mint)}
.lead{margin-top:18px;font-size:clamp(1.02rem,.95rem + .45vw,1.2rem);color:#E6F2F0;max-width:34em}
.nf{margin-top:26px;max-width:520px}
.nf__row{display:flex;gap:8px;padding:6px;border-radius:18px;background:#fff;box-shadow:0 14px 40px rgba(0,0,0,.28)}
.nf__in{flex:1;min-width:0;border:0;background:transparent;font:inherit;font-size:1rem;padding:0 14px;height:50px;color:var(--ink);border-radius:12px}
.nf__in::placeholder{color:#6A7773}
.nf__in:focus{outline:none}
.nf__row:focus-within{box-shadow:0 14px 40px rgba(0,0,0,.28),0 0 0 3px var(--mint)}
.nf__in[aria-invalid="true"]{color:#8A1427}
.nf__btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:13px;padding:0 20px;height:50px;font:inherit;font-weight:750;font-size:.98rem;color:#fff;background:var(--teal);cursor:pointer;transition:background .2s,transform .2s var(--ease)}
.nf__btn:hover{background:var(--teal-d)}
.nf__btn:active{transform:scale(.97)}
.nf__hint{margin-top:10px;font-size:.84rem;color:rgba(255,255,255,.82)}
.nf__ok{display:flex;gap:10px;align-items:flex-start;margin-top:12px;padding:12px 14px;border-radius:14px;background:rgba(123,224,160,.16);border:1px solid rgba(123,224,160,.5);color:#fff;font-size:.95rem;animation:rise .5s var(--ease) both}
.nf__ok[hidden]{display:none}
.nf__ok .i{color:#7BE0A0;margin-top:3px;font-size:1.2em}
.nf.is-done .nf__row,.nf.is-done .nf__hint{display:none}
.scroll{position:absolute;left:50%;bottom:18px;transform:translateX(-50%);color:#fff;opacity:.85;text-decoration:none;width:44px;height:44px;display:grid;place-items:center;border-radius:50%;border:1px solid rgba(255,255,255,.35);animation:bob 2.4s ease-in-out infinite}
.scroll .i{transform:rotate(90deg)}
/* sections */
main{position:relative}
.sec{padding:clamp(48px,7vw,88px) 0}
.sec__h{font-family:var(--serif);font-weight:400;font-size:clamp(1.8rem,1.2rem + 2.2vw,2.7rem);line-height:1.1;color:var(--pine);text-wrap:balance}
.sec__s{margin-top:10px;color:var(--muted);max-width:40em}
.chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;font-size:.74rem;font-weight:750;letter-spacing:.01em;white-space:nowrap}
.chip .i{font-size:1em}
.chip--live{background:#DDF3E1;color:#17652B}
.chip--soon{background:#EEF1F0;color:#46544F;border:1px solid var(--line)}
.launch{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:28px}
.card{display:flex;flex-direction:column;background:#fff;border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:0 1px 2px rgba(12,40,36,.04),0 10px 30px rgba(12,40,36,.07);transition:transform .35s var(--ease),box-shadow .35s var(--ease)}
.card:hover{transform:translateY(-4px);box-shadow:0 20px 44px rgba(12,40,36,.13)}
.card__img{aspect-ratio:16/10;overflow:hidden;background:var(--tint2)}
.card__img img{width:100%;height:100%;object-fit:cover;transition:transform .8s var(--ease)}
.card:hover .card__img img{transform:scale(1.05)}
.card__b{padding:20px 22px 24px;display:flex;flex-direction:column;gap:10px}
.card__t{display:flex;align-items:center;gap:10px;font-family:var(--serif);font-size:1.6rem;line-height:1.1;color:var(--pine)}
.card__t .ico{width:42px;height:42px;border-radius:13px;background:var(--tint);color:var(--teal);display:grid;place-items:center;flex:none;font-size:1.25rem}
.card p{color:var(--ink2);font-size:.96rem}
.soon{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:26px}
.tile{position:relative;display:flex;flex-direction:column;gap:12px;padding:14px;background:rgba(255,255,255,.7);border:1px dashed #BFD3CD;border-radius:18px}
.tile__img{aspect-ratio:4/3;border-radius:12px;overflow:hidden;background:var(--tint2)}
.tile__img img{width:100%;height:100%;object-fit:cover;filter:grayscale(.55) brightness(1.02);opacity:.85}
.tile__t{display:flex;align-items:center;gap:8px;font-weight:750;font-size:.98rem;color:var(--ink)}
.tile__t .i{color:var(--teal);font-size:1.15em}
.how{background:#fff;border-block:1px solid var(--line)}
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:28px;counter-reset:s}
.step{padding:22px;border-radius:20px;background:var(--tint);position:relative}
.step .ico{width:46px;height:46px;border-radius:14px;background:var(--teal);color:#fff;display:grid;place-items:center;font-size:1.3rem;margin-bottom:14px}
.step h3{font-size:1.05rem;font-weight:750;margin-bottom:4px}
.step p{color:var(--ink2);font-size:.93rem}
.trust{display:flex;flex-wrap:wrap;gap:10px 22px;margin-top:26px;color:var(--ink2);font-weight:650;font-size:.92rem}
.trust span{display:inline-flex;gap:8px;align-items:center}
.trust .i{color:var(--teal);font-size:1.2em}
.cta{background:linear-gradient(135deg,var(--pine) 0%,var(--teal) 100%);color:#fff;border-radius:28px;padding:clamp(28px,5vw,56px);display:grid;grid-template-columns:1.1fr 1fr;gap:28px;align-items:center;position:relative;overflow:hidden}
.cta .sec__h{color:#fff}
.cta p.s{margin-top:10px;color:rgba(255,255,255,.85)}
.cta .nf{margin-top:0;max-width:none}
.cta .nf__hint{color:rgba(255,255,255,.85)}
footer{padding:36px 0 calc(36px + env(safe-area-inset-bottom));color:rgba(255,255,255,.78);font-size:.88rem;background:var(--pine-d);border-top:4px solid var(--season)}
footer .wrap{display:flex;flex-wrap:wrap;gap:10px 24px;justify-content:space-between;align-items:center}
/* entrance + motion */
.in{animation:riseT .8s var(--ease) both;animation-delay:calc(var(--d,0) * 110ms)}
.rv{opacity:0;transform:translateY(22px);transition:opacity .7s var(--ease),transform .7s var(--ease);transition-delay:calc(var(--d,0) * 90ms)}
.rv.on,.no-js .rv{opacity:1;transform:none}
@keyframes riseT{from{transform:translateY(14px)}to{transform:none}}
@keyframes rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
@keyframes kb{from{transform:scale(1.04)}to{transform:scale(1.12) translate(-1.2%,-1%)}}
@keyframes snow{from{background-position:0 0}to{background-position:-60px 240px}}
@keyframes pulse{70%{box-shadow:0 0 0 9px rgba(123,224,160,0)}100%{box-shadow:0 0 0 0 rgba(123,224,160,0)}}
@keyframes bob{50%{transform:translate(-50%,6px)}}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  *,*::before,*::after{animation:none!important;transition:none!important}
  .rv{opacity:1;transform:none}
  .snow{display:none}
}
/* tablet / mobile: app-like */
@media (max-width:900px){
  .launch{grid-template-columns:1fr}
  .soon{grid-template-columns:1fr 1fr}
  .steps{grid-template-columns:1fr}
  .cta{grid-template-columns:1fr}
}
@media (max-width:600px){
  .wrap{width:calc(100% - 32px)}
  .bar .logo__text{font-size:15px!important;white-space:nowrap}.bar .logo img{width:36px;height:35px}
  .pill{font-size:.72rem;padding:6px 11px;white-space:nowrap}.pill__l{display:none}.pill__s{display:inline}
  .hero__main{align-items:flex-end;padding:12px 0 84px}
  .hero__bg img{object-position:58% 50%}
  .nf__row{flex-direction:column;padding:8px}
  .nf__in{flex:none;width:100%;height:52px;background:#F2F6F5;border-radius:12px}
  .nf__btn{height:52px;justify-content:center}
  .card{border-radius:20px}
  .card__img{aspect-ratio:16/9}
  .card__t{font-size:1.4rem}
  .tile{padding:10px;gap:10px;border-radius:16px}
  .tile__t{font-size:.9rem}
  .tile .chip{font-size:.68rem;padding:4px 9px}
  .scroll{bottom:calc(16px + env(safe-area-inset-bottom))}
}
</style>
<script>document.documentElement.className+=" js"</script>
</head>
<body class="no-js">
<script>document.body.classList.remove("no-js")</script>
<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
<symbol id="snow" viewBox="0 0 16 16"><path d="M8 16a.5.5 0 0 1-.5-.5v-1.293l-.646.647a.5.5 0 0 1-.707-.708L7.5 12.793V8.866l-3.4 1.963-.496 1.85a.5.5 0 1 1-.966-.26l.237-.882-1.12.646a.5.5 0 0 1-.5-.866l1.12-.646-.884-.237a.5.5 0 1 1 .26-.966l1.848.495L7 8 3.6 6.037l-1.85.495a.5.5 0 0 1-.258-.966l.883-.237-1.12-.646a.5.5 0 1 1 .5-.866l1.12.646-.237-.883a.5.5 0 1 1 .966-.258l.495 1.849L7.5 7.134V3.207L6.147 1.854a.5.5 0 1 1 .707-.708l.646.647V.5a.5.5 0 1 1 1 0v1.293l.647-.647a.5.5 0 1 1 .707.708L8.5 3.207v3.927l3.4-1.963.496-1.85a.5.5 0 1 1 .966.26l-.236.882 1.12-.646a.5.5 0 0 1 .5.866l-1.12.646.883.237a.5.5 0 1 1-.26.966l-1.848-.495L9 8l3.4 1.963 1.849-.495a.5.5 0 0 1 .259.966l-.883.237 1.12.646a.5.5 0 0 1-.5.866l-1.12-.646.236.883a.5.5 0 1 1-.966.258l-.495-1.849-3.4-1.963v3.927l1.353 1.353a.5.5 0 0 1-.707.708l-.647-.647V15.5a.5.5 0 0 1-.5.5z"/></symbol>
<symbol id="wrench-adjustable" viewBox="0 0 16 16"><path d="M16 4.5a4.5 4.5 0 0 1-1.703 3.526L13 5l2.959-1.11q.04.3.041.61"/>
  <path d="M11.5 9c.653 0 1.273-.139 1.833-.39L12 5.5 11 3l3.826-1.53A4.5 4.5 0 0 0 7.29 6.092l-6.116 5.096a2.583 2.583 0 1 0 3.638 3.638L9.908 8.71A4.5 4.5 0 0 0 11.5 9m-1.292-4.361-.596.893.809-.27a.25.25 0 0 1 .287.377l-.596.893.809-.27.158.475-1.5.5a.25.25 0 0 1-.287-.376l.596-.893-.809.27a.25.25 0 0 1-.287-.377l.596-.893-.809.27-.158-.475 1.5-.5a.25.25 0 0 1 .287.376M3 14a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/></symbol>
<symbol id="trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
  <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></symbol>
<symbol id="tree" viewBox="0 0 16 16"><path d="M8.416.223a.5.5 0 0 0-.832 0l-3 4.5A.5.5 0 0 0 5 5.5h.098L3.076 8.735A.5.5 0 0 0 3.5 9.5h.191l-1.638 3.276a.5.5 0 0 0 .447.724H7V16h2v-2.5h4.5a.5.5 0 0 0 .447-.724L12.31 9.5h.191a.5.5 0 0 0 .424-.765L10.902 5.5H11a.5.5 0 0 0 .416-.777zM6.437 4.758A.5.5 0 0 0 6 4.5h-.066L8 1.401 10.066 4.5H10a.5.5 0 0 0-.424.765L11.598 8.5H11.5a.5.5 0 0 0-.447.724L12.69 12.5H3.309l1.638-3.276A.5.5 0 0 0 4.5 8.5h-.098l2.022-3.235a.5.5 0 0 0 .013-.507"/></symbol>
<symbol id="scissors" viewBox="0 0 16 16"><path d="M3.5 3.5c-.614-.884-.074-1.962.858-2.5L8 7.226 11.642 1c.932.538 1.472 1.616.858 2.5L8.81 8.61l1.556 2.661a2.5 2.5 0 1 1-.794.637L8 9.73l-1.572 2.177a2.5 2.5 0 1 1-.794-.637L7.19 8.61zm2.5 10a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0m7 0a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/></symbol>
<symbol id="car-front" viewBox="0 0 16 16"><path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0m10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0M6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2zM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17s2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276"/>
  <path d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679q.05.242.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.8.8 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.8.8 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155s4.037-.084 5.592-.155A1.48 1.48 0 0 0 15 9.611v-.413q0-.148-.03-.294l-.335-1.68a.8.8 0 0 0-.43-.563 1.8 1.8 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3z"/></symbol>
<symbol id="shield-check" viewBox="0 0 16 16"><path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/> <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/></symbol>
<symbol id="bell" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/></symbol>
<symbol id="envelope" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></symbol>
<symbol id="lock-fill" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2"/></symbol>
<symbol id="geo-alt" viewBox="0 0 16 16"><path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10"/> <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/></symbol>
<symbol id="check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></symbol>
<symbol id="camera" viewBox="0 0 16 16"><path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4z"/>
  <path d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5m0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M3 6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0"/></symbol>
<symbol id="credit-card" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/><path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></symbol>
<symbol id="arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></symbol>
<symbol id="people" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/></symbol>
<symbol id="stars" viewBox="0 0 16 16"><path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/></symbol>
</svg>

<header class="hero">
  <div class="hero__bg">
    <picture>
      <source media="(max-width: 700px)" type="image/avif" srcset="<?= $ph ?>hero-winter-house-m-480.avif 480w, <?= $ph ?>hero-winter-house-m-800.avif 800w" sizes="100vw">
      <source media="(max-width: 700px)" type="image/webp" srcset="<?= $ph ?>hero-winter-house-m-480.webp 480w, <?= $ph ?>hero-winter-house-m-800.webp 800w" sizes="100vw">
      <source type="image/avif" srcset="<?= $ph ?>hero-winter-house-768.avif 768w, <?= $ph ?>hero-winter-house-1280.avif 1280w, <?= $ph ?>hero-winter-house-1920.avif 1920w" sizes="100vw">
      <source type="image/webp" srcset="<?= $ph ?>hero-winter-house-768.webp 768w, <?= $ph ?>hero-winter-house-1280.webp 1280w, <?= $ph ?>hero-winter-house-1920.webp 1920w" sizes="100vw">
      <img src="<?= $ph ?>hero-winter-house-1920.jpg" alt="" width="1920" height="1080" fetchpriority="high" decoding="async">
    </picture>
  </div>
  <div class="snow" aria-hidden="true"></div><div class="snow snow--b" aria-hidden="true"></div>

  <div class="wrap bar in">
    <a class="brandlink" href="<?= nu_e(nu_url('public/coming_soon.php')) ?>" aria-label="Nuahn Seasonal Hub"><?= nu_logo(46, 'dark') ?></a>
    <span class="pill"><span class="dot"></span><span>Opening <span class="pill__l">end of November 2026</span><span class="pill__s">Nov 2026</span></span></span>
  </div>

  <div class="wrap hero__main">
    <div class="hero__copy">
      <p class="eyebrow in" style="--d:1">Coming soon · Canada</p>
      <h1 class="in" style="--d:2">Every season. <em>One trusted hub.</em></h1>
      <p class="lead in" style="--d:3">Nuahn is a trusted marketplace connecting homeowners with vetted local independent pros for Canadian seasonal care.</p>
      <div class="in" style="--d:4"><?= $form('notify-hero') ?></div>
    </div>
  </div>
  <a class="scroll" href="#launch" aria-label="See what launches first"><svg class="i" aria-hidden="true"><use href="#arrow-right"/></svg></a>
</header>

<main>
  <section class="sec" id="launch" aria-labelledby="l-h">
    <div class="wrap">
      <h2 class="sec__h rv" id="l-h">Launching first</h2>
      <p class="sec__s rv" style="--d:1">We open end of November with two services, so every job gets the care it deserves. More follow soon.</p>
      <div class="launch">
<?php $d = 0; foreach ($live as $slug => $sv): ?>
        <article class="card rv" style="--d:<?= $d++ ?>">
          <div class="card__img"><picture>
            <source type="image/avif" srcset="<?= $ph . $sv['photo'] ?>-400.avif 400w, <?= $ph . $sv['photo'] ?>-800.avif 800w" sizes="(max-width: 900px) 92vw, 540px">
            <source type="image/webp" srcset="<?= $ph . $sv['photo'] ?>-400.webp 400w, <?= $ph . $sv['photo'] ?>-800.webp 800w" sizes="(max-width: 900px) 92vw, 540px">
            <img src="<?= $ph . $sv['photo'] ?>-800.jpg" alt="<?= nu_e($sv['alt']) ?>" width="800" height="500" loading="lazy" decoding="async">
          </picture></div>
          <div class="card__b">
            <span><span class="chip chip--live"><svg class="i" aria-hidden="true"><use href="#check-circle-fill"/></svg>Launching first, end of November 2026</span></span>
            <h3 class="card__t"><span class="ico"><svg class="i" aria-hidden="true"><use href="#<?= nu_e($sv['icon']) ?>"/></svg></span><?= nu_e($sv['name'] === 'Snow Removal' ? 'Winter Snow Removal' : $sv['name']) ?></h3>
            <p><?= nu_e($blurb[$slug] ?? $sv['blurb']) ?></p>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sec" style="padding-top:0" aria-labelledby="s-h">
    <div class="wrap">
      <h2 class="sec__h rv" id="s-h">Coming soon</h2>
      <p class="sec__s rv" style="--d:1">Four more seasonal services are on the way. Leave your email and we’ll tell you when each one opens.</p>
      <div class="soon">
<?php $d = 0; foreach ($soon as $slug => $sv): ?>
        <div class="tile rv" style="--d:<?= $d++ ?>">
          <div class="tile__img"><picture>
            <source type="image/avif" srcset="<?= $ph . $sv['photo'] ?>-400.avif" >
            <source type="image/webp" srcset="<?= $ph . $sv['photo'] ?>-400.webp">
            <img src="<?= $ph . $sv['photo'] ?>-400.webp" alt="" width="400" height="300" loading="lazy" decoding="async">
          </picture></div>
          <div class="tile__t"><svg class="i" aria-hidden="true"><use href="#<?= nu_e($sv['icon']) ?>"/></svg><?= nu_e($sv['name']) ?></div>
          <span><span class="chip chip--soon"><svg class="i" aria-hidden="true"><use href="#lock-fill"/></svg>Coming soon</span></span>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="sec how" aria-labelledby="h-h">
    <div class="wrap">
      <h2 class="sec__h rv" id="h-h">Seasonal care, made simple</h2>
      <div class="steps">
        <div class="step rv"><span class="ico"><svg class="i" aria-hidden="true"><use href="#people"/></svg></span><h3>Meet vetted local pros</h3><p>Independent professionals in your area, checked before they join Nuahn.</p></div>
        <div class="step rv" style="--d:1"><span class="ico"><svg class="i" aria-hidden="true"><use href="#geo-alt"/></svg></span><h3>Book and track in one place</h3><p>Tell us what you need, see a clear price, and follow your job from request to done.</p></div>
        <div class="step rv" style="--d:2"><span class="ico"><svg class="i" aria-hidden="true"><use href="#camera"/></svg></span><h3>See the finished work</h3><p>Photos of completed work and a simple record of every job, all in your account.</p></div>
      </div>
      <div class="trust rv">
        <span><svg class="i" aria-hidden="true"><use href="#shield-check"/></svg>Vetted independent pros</span>
        <span><svg class="i" aria-hidden="true"><use href="#credit-card"/></svg>Clear prices up front</span>
        <span><svg class="i" aria-hidden="true"><use href="#lock-fill"/></svg>Secure payments</span>
      </div>
    </div>
  </section>

  <section class="sec" aria-labelledby="c-h">
    <div class="wrap">
      <div class="cta rv">
        <div><h2 class="sec__h" id="c-h">Be first through the door</h2><p class="s">Get one email the day Winter Snow Removal and Mobile Mechanics open.</p></div>
        <div><?= $form('notify-cta') ?></div>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <a class="brandlink" href="<?= nu_e(nu_url('public/coming_soon.php')) ?>" aria-label="Nuahn Seasonal Hub"><?= nu_logo(34, 'dark') ?></a>
    <span>© <?= date('Y') ?> Nuahn Seasonal Hub · Canadian seasonal care</span>
  </div>
</footer>

<script>
(function(){
  // UI only: nothing is sent or stored. Sample behaviour for the "Notify me" form.
  var re=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  document.querySelectorAll("[data-notify]").forEach(function(f){
    f.addEventListener("submit",function(e){
      var i=f.querySelector("input[type=email]"),h=f.querySelector("[data-hint]"),ok=f.querySelector("[data-ok]");
      e.preventDefault();
      if(!re.test(i.value.trim())){i.setAttribute("aria-invalid","true");h.textContent="Please enter a valid email address.";h.style.color="#FFD6DC";i.focus();return}
      i.removeAttribute("aria-invalid");f.classList.add("is-done");ok.hidden=false;
    });
  });
  var els=document.querySelectorAll(".rv");
  if(!("IntersectionObserver" in window)){els.forEach(function(e){e.classList.add("on")});return}
  var io=new IntersectionObserver(function(en){en.forEach(function(x){if(x.isIntersecting){x.target.classList.add("on");io.unobserve(x.target)}})},{rootMargin:"0px 0px -8% 0px"});
  els.forEach(function(e){io.observe(e)});
})();
</script>
</body>
</html>
