<?php get_header(); ?>
<div class="event_wrapper">
	<?php 
	$lumia_calender = new Lumia_Calender;
	$lumia_calender->get_single_event();
	?>
</div>
<?php get_footer(); ?>