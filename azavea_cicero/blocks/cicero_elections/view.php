<h2>Upcoming Elections</h2>
<ul class="election-events">
<?php foreach ($events as $event) { ?>
    <li class="election-event">
        <div class="election-event-data"><?php echo $event->election_date_text;?> </div>
        <div class="election-event-label"><?php echo $event->label;?> </div>
        <?php if($event->remarks != '') { ?>
            <div class="election-event-remarks"><?php echo $event->remarks;?> </div>
        <?php } ?>
    </li>
<?php } ?>
</ul>