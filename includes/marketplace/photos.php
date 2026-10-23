<?php
// UI design & front-end development: Christo Innocent
//   Email: xristoinno@gmail.com | Phone: +256783335335
// (Server-side comment only; never output to the browser.)
// includes/marketplace/photos.php: job photo + vetting document uploads.
// Safety rules: PHP upload error check, size limit, real MIME type from finfo (not the browser's claim), image decode
// check, random server-chosen filenames (the client's filename is discarded), and a GD RE-ENCODE that drops EXIF
// (GPS, camera, owner) and any payload hidden in the file. Files are stored under uploads/jobs/ with a deny-all
// .htaccess and are served only through public/media.php after a permission check.

require_once __DIR__ . '/core.php';

function mk_upload_dir($sub) {
    $base = MK_UPLOAD_DIR;
    $dir = $base . '/' . $sub;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) throw new MkError('Uploads are not available right now. Please try again later.', 409);
    $ht = $base . '/.htaccess';
    if (!is_file($ht)) { @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n"); }
    return $dir;
}

/** Normalise $_FILES[$field] (single or multiple) to a list of file arrays. */
function mk_files($field) {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field]; $out = [];
    if (is_array($f['name'])) {
        foreach ($f['name'] as $i => $n) { $out[] = ['name' => $n, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]]; }
    } else { $out[] = $f; }
    return array_values(array_filter($out, fn($x) => (int) $x['error'] !== UPLOAD_ERR_NO_FILE));
}

/** Validate and re-encode one uploaded image. @return array [path(abs), mime, bytes, w, h, sha256] */
function mk_image_reencode(array $file, $destDirAbs) {
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new MkError($file['error'] == UPLOAD_ERR_INI_SIZE || $file['error'] == UPLOAD_ERR_FORM_SIZE ? 'That photo is too large (max 10 MB).' : 'The upload failed. Please try again.');
    }
    if (!is_uploaded_file($file['tmp_name'])) throw new MkError('The upload failed. Please try again.');
    if ((int) $file['size'] > MK_MAX_PHOTO_BYTES || (int) $file['size'] <= 0) throw new MkError('Photos must be under 10 MB.');
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new MkError('Only JPG, PNG or WebP photos are accepted.');
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 10 || $info[1] < 10 || $info[0] > 12000 || $info[1] > 12000) throw new MkError('That file is not a usable photo.');
    if (!function_exists('imagecreatefromstring')) throw new MkError('Photo processing is not available on this server.', 409);
    $img = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$img) throw new MkError('That file is not a usable photo.');
    // respect the camera orientation before the EXIF is dropped
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($file['tmp_name']);
        $o = (int) ($ex['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) { $rot = imagerotate($img, $angle, 0); if ($rot) { imagedestroy($img); $img = $rot; } }
    }
    $w = imagesx($img); $h = imagesy($img); $max = 2400;
    if (max($w, $h) > $max) {
        $r = $max / max($w, $h);
        $n = imagescale($img, (int) round($w * $r), (int) round($h * $r));
        if ($n) { imagedestroy($img); $img = $n; $w = imagesx($img); $h = imagesy($img); }
    }
    $name = bin2hex(random_bytes(16)) . '.jpg';
    $abs = rtrim($destDirAbs, '/') . '/' . $name;
    // flatten transparency onto white, write a plain baseline JPEG
    $flat = imagecreatetruecolor($w, $h);
    imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
    imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
    imagedestroy($img);
    if (!imagejpeg($flat, $abs, 85)) { imagedestroy($flat); throw new MkError('The photo could not be saved.', 409); }
    imagedestroy($flat);
    @chmod($abs, 0644);
    return ['path' => $abs, 'name' => $name, 'mime' => 'image/jpeg', 'bytes' => (int) filesize($abs), 'w' => $w, 'h' => $h, 'sha' => hash_file('sha256', $abs)];
}

function mk_photo_counts($taskId) {
    $st = mk_db()->prepare('SELECT kind, COUNT(*) FROM job_photos WHERE task_id = ? GROUP BY kind');
    $st->execute([(int) $taskId]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR) + ['before' => 0, 'after' => 0, 'dispute' => 0, 'issue' => 0];
}
function mk_photos($taskId, $kind = null) {
    $sql = 'SELECT * FROM job_photos WHERE task_id = ?'; $args = [(int) $taskId];
    if ($kind) { $sql .= ' AND kind = ?'; $args[] = $kind; }
    $st = mk_db()->prepare($sql . ' ORDER BY id');
    $st->execute($args);
    return $st->fetchAll();
}
function mk_photo_url($id) { return nu_url('public/media.php') . '?p=' . (int) $id; }

/** Store the uploaded files of $field for a task. $kind before|after|dispute|issue. @return int stored count */
function mk_photo_store($taskId, $userId, $kind, $field = 'photo') {
    if (!in_array($kind, ['before', 'after', 'dispute', 'issue'], true)) throw new MkError('Choose before or after.');
    $files = mk_files($field);
    if (!$files) throw new MkError('Choose at least one photo to upload.');
    $have = mk_photo_counts($taskId)[$kind] ?? 0;
    if ($have + count($files) > MK_MAX_PHOTOS_PER_KIND) throw new MkError('You can add up to ' . MK_MAX_PHOTOS_PER_KIND . ' ' . $kind . ' photos.');
    $dir = mk_upload_dir((int) $taskId);
    $n = 0;
    foreach ($files as $f) {
        $r = mk_image_reencode($f, $dir);
        mk_db()->prepare("INSERT INTO job_photos (task_id, user_id, kind, storage, path, mime, bytes, width, height, sha256) VALUES (?,?,?, 'upload', ?,?,?,?,?,?)")
            ->execute([(int) $taskId, (int) $userId, $kind, (int) $taskId . '/' . $r['name'], $r['mime'], $r['bytes'], $r['w'], $r['h'], $r['sha']]);
        $n++;
    }
    mk_audit('photo.added', 'task', $taskId, "$n $kind photo(s)");
    return $n;
}

/** Vetting document (PDF or image). Stored under uploads/jobs/_docs/{userId}/. */
function mk_doc_store($userId, $kind, $field = 'doc', $expires = null) {
    if (!in_array($kind, ['identity', 'insurance', 'licence', 'cert'], true)) throw new MkError('Choose a document type.');
    $files = mk_files($field);
    if (!$files) throw new MkError('Choose a file to upload.');
    $f = $files[0];
    if ((int) $f['error'] !== UPLOAD_ERR_OK) throw new MkError('The upload failed. Please try again.');
    if (!is_uploaded_file($f['tmp_name'])) throw new MkError('The upload failed. Please try again.');
    if ((int) $f['size'] > MK_MAX_PHOTO_BYTES || (int) $f['size'] <= 0) throw new MkError('Documents must be under 10 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $dir = mk_upload_dir('_docs/' . (int) $userId);
    if ($mime === 'application/pdf' && strncmp((string) file_get_contents($f['tmp_name'], false, null, 0, 5), '%PDF-', 5) === 0) {
        $name = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new MkError('The file could not be saved.', 409);
        $out = ['mime' => 'application/pdf', 'name' => $name, 'bytes' => (int) filesize($dir . '/' . $name)];
    } else {
        $r = mk_image_reencode($f, $dir);
        $out = ['mime' => 'image/jpeg', 'name' => $r['name'], 'bytes' => $r['bytes']];
    }
    $exp = ($expires && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) ? $expires : null;
    mk_db()->prepare('INSERT INTO contractor_documents (user_id, kind, path, mime, bytes, expires_on) VALUES (?,?,?,?,?,?)')
        ->execute([(int) $userId, $kind, '_docs/' . (int) $userId . '/' . $out['name'], $out['mime'], $out['bytes'], $exp]);
    $col = ['identity' => 'chk_identity', 'insurance' => 'chk_insurance', 'licence' => 'chk_licence', 'cert' => 'chk_cert'][$kind];
    mk_db()->prepare("UPDATE contractor_profiles SET $col = 'review' WHERE user_id = ? AND $col IN ('missing','rejected','na')")->execute([(int) $userId]);
    mk_audit('doc.uploaded', 'user', $userId, $kind);
}
