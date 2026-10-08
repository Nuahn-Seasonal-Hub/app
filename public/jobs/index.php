<td>
  <?php if ($job['latitude'] && $job['longitude']): ?>
    <a href="https://www.google.com/maps?q=<?php echo $job['latitude']; ?>,<?php echo $job['longitude']; ?>" target="_blank">View Location</a>
  <?php endif; ?>
</td>
<td>
  <?php if ($job['image_path']): ?>
    <img src="<?php echo $job['image_path']; ?>" alt="Job Site" style="width:100px;height:auto;">
  <?php endif; ?>
</td>
