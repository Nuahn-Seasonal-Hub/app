<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// public/contractor_setup.php: contractor profile, documents and "submit for vetting". Nuahn staff review the
// application in operations.php (?view=vetting). Only an approved profile can send offers and quotes.
require_once("../config/init.php");
requireLogin('provider');
require_once("../includes/marketplace/view.php");
$uid = mk_uid();
$p = mk_profile_ensure($uid);
$docs = mk_documents($uid);
$trades = mk_trades($p);
$status = $p['vetting_status'];
$labels = ['draft' => ['Not submitted', 'chip--plain'], 'pending' => ['Waiting for review', 'chip--warning'], 'in_review' => ['In review', 'chip--warning'],
           'changes_requested' => ['Changes requested', 'chip--warning'], 'approved' => ['Approved', 'chip--success'], 'rejected' => ['Not approved', 'chip--danger']];
$chk = ['identity' => ['Government ID', 'chk_identity'], 'insurance' => ['Liability insurance', 'chk_insurance'], 'licence' => ['Trade licence (mechanics)', 'chk_licence'], 'cert' => ['Certificate (optional)', 'chk_cert']];
$chkWords = ['missing' => 'Not uploaded', 'review' => 'Uploaded, waiting for review', 'ok' => 'Verified', 'rejected' => 'Rejected, upload again', 'na' => 'Not required'];
$locked = in_array($status, ['pending', 'in_review'], true);
include_once("../includes/header.php");
?>
<div class="console">
  <?= nu_console_nav(nu_provider_nav(), 'profile', 'Contractor workspace', 'Get approved,<br>then start<br>earning.') ?>
  <main class="console__main">
    <header class="pd-head">
      <div><h1>My contractor profile</h1><p>Nuahn checks identity, insurance and licences before anyone can take jobs.</p></div>
      <span class="chip <?= nu_e($labels[$status][1]) ?>"><?= nu_e($labels[$status][0]) ?></span>
    </header>

<?php if (!empty($p['vet_note']) && in_array($status, ['changes_requested', 'rejected', 'approved'], true)): ?>
    <div class="mk-note<?= $status === 'approved' ? '' : ' mk-note--warn' ?>"><?= nu_icon($status === 'approved' ? 'check-circle-fill' : 'info-circle') ?><div><b>Note from Nuahn</b><p><?= nu_e($p['vet_note']) ?></p></div></div>
<?php endif; ?>
<?php if ($status === 'approved'): ?>
    <div class="mk-note"><?= nu_icon('shield-check') ?><div><b>You are approved.</b><p>You can send offers and quotes. <a href="<?= nu_e(nu_url('public/open_jobs.php')) ?>">See open jobs</a></p></div></div>
<?php endif; ?>

    <section class="sv-card mk-sec">
      <h2>Services and base</h2>
      <?= mk_form('profile_save', [], 'class="mk-form"', 'contractor_setup.php') ?>
        <fieldset class="fld"><legend class="label">Services you offer</legend>
          <label class="mk-check"><input type="checkbox" name="trades[]" value="snow-removal" <?= in_array('snow-removal', $trades, true) ? 'checked' : '' ?>> Winter snow removal</label>
          <label class="mk-check"><input type="checkbox" name="trades[]" value="mobile-mechanics" <?= in_array('mobile-mechanics', $trades, true) ? 'checked' : '' ?>> Mobile mechanics</label>
        </fieldset>
        <label class="fld"><span class="label">Headline</span><input class="input" name="headline" maxlength="160" value="<?= nu_e($p['headline']) ?>" placeholder="e.g. Reliable driveway clearing, 6 years in Edmonton"></label>
        <label class="fld"><span class="label">About you</span><textarea class="input" name="about" rows="3" maxlength="1500"><?= nu_e($p['about']) ?></textarea></label>
        <label class="fld"><span class="label">Equipment</span><input class="input" name="equipment" maxlength="255" value="<?= nu_e($p['equipment']) ?>" placeholder="e.g. 2 trucks with plows, snow blowers, ice melt"></label>
        <?= nu_map_picker(['name' => 'base', 'label' => 'Base address (where you start from)', 'address' => $p['base_address'], 'lat' => $p['base_lat'] ?? 53.4891, 'lng' => $p['base_lng'] ?? -113.5262, 'placeholder' => 'Your yard, shop or home address']) ?>
        <label class="fld"><span class="label">How far will you travel? (km)</span><input class="input" type="number" name="radius_km" min="1" max="100" value="<?= (int) $p['radius_km'] ?>"></label>
        <button class="btn btn-primary" type="submit"><?= nu_icon('check2') ?> Save profile</button>
      </form>
    </section>

    <section class="sv-card mk-sec">
      <h2>Documents</h2>
      <p class="mk-sub">PDF, JPG or PNG up to 10 MB. Files are private: only Nuahn reviewers can open them.</p>
      <ul class="mk-docs">
<?php foreach ($chk as $k => [$label, $col]): ?>
        <li><span><b><?= nu_e($label) ?></b><small><?= nu_e($chkWords[$p[$col]] ?? $p[$col]) ?></small></span>
          <span class="chip <?= $p[$col] === 'ok' ? 'chip--success' : ($p[$col] === 'rejected' ? 'chip--danger' : 'chip--plain') ?>"><?= nu_e($p[$col] === 'ok' ? 'Verified' : ($p[$col] === 'na' ? 'N/A' : ucfirst($p[$col]))) ?></span></li>
<?php endforeach; ?>
      </ul>
<?php if (!$locked && $status !== 'approved'): ?>
      <?= mk_form('doc_upload', [], 'class="mk-form mk-form--row" enctype="multipart/form-data"', 'contractor_setup.php') ?>
        <label class="fld"><span class="label">Document type</span><select class="input" name="kind"><?php foreach ($chk as $k => [$label]): ?><option value="<?= nu_e($k) ?>"><?= nu_e($label) ?></option><?php endforeach; ?></select></label>
        <label class="fld"><span class="label">File</span><input class="input" type="file" name="doc[]" accept="application/pdf,image/jpeg,image/png" required></label>
        <label class="fld"><span class="label">Expires (optional)</span><input class="input" type="date" name="expires_on"></label>
        <button class="btn btn-outline" type="submit"><?= nu_icon('upload') ?> Upload</button>
      </form>
<?php endif; ?>
<?php if ($docs): ?>
      <ul class="mk-docs mk-docs--files"><?php foreach ($docs as $d): ?><li><span><?= nu_icon('file-earmark-text') ?> <?= nu_e($chk[$d['kind']][0] ?? $d['kind']) ?><small><?= nu_e(mk_dt($d['created_at'])) ?><?= $d['expires_on'] ? ' · expires ' . nu_e($d['expires_on']) : '' ?></small></span></li><?php endforeach; ?></ul>
<?php endif; ?>
    </section>

<?php if (in_array($status, ['draft', 'changes_requested', 'rejected'], true)): ?>
    <section class="sv-card mk-sec">
      <h2>Submit for review</h2>
      <p class="mk-sub">Save your profile and upload your ID and insurance first. Mechanics also need a trade licence.</p>
      <?= mk_form('profile_submit', [], '', 'contractor_setup.php') ?><button class="btn btn-primary" type="submit"><?= nu_icon('send') ?> Submit my application</button></form>
    </section>
<?php endif; ?>
  </main>
</div>
<script src="<?= nu_e(nu_asset('public/assets/map-picker.js')) ?>" defer></script>
<?php include_once("../includes/footer.php"); ?>
