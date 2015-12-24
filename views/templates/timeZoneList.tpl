<?php  
$timeZones = get_data('timeZones');
?>

<select class="js-time-zone-list" name="timezone">
    <?php foreach($timeZones as $timeZone): ?>
    <option value="<?= $timeZone['value'] ?>">
        <?= $timeZone['label'] ?>
    </option>
    <?php endforeach ?>
</select>
                 