<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

use Sprout\Helpers\Inflector;


$single = Inflector::singular($friendly_name);
?>


<div class="message-bar-warning">
    <p>Are you sure you want to delete this <?php echo $single; ?>?</p>
    <p>Deleting a <?php echo $single; ?> is an irreversible action.</p>
</div>
