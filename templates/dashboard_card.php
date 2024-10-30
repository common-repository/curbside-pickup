<div class="col-md-3 mb-2 mt-2">
	<div class="curbside_pickup_dashboard_customer_row card border border-secondary" data-order-id="<?php echo htmlspecialchars($pickup_id) ?>">
		<div class="container">
			<div class="row">
				<div class="xd-flex align-items-center card-top bg-info p-0 col-3 text-center" <?php echo $badge_style; ?>>
					<span class="d-block display-5 w-100"><span class="align-top d-inline-block badge m-0 mb-1 card-title text-white curbside_pickup_dashboard_list_customer_initials" <?php echo $badge_text_style; ?>><?php echo htmlspecialchars($customer_initials); ?></span></span>
					<div class="d-block w-100"><span class="bg-dark align-top d-inline-block badge m-0 mb-1 card-title text-white curbside_pickup_dashboard_list_customer_initials" style="font-size:1.25em"><?php echo htmlspecialchars($scheduled_pickup_time); ?></span></div>
				</div>
				<div class="d-flex align-items-center card-top bg-dashboard-card text-white p-0 pl-2 col" >
					<h5 class="align-middle d-inline-block m-0 card-title curbside_pickup_dashboard_list_customer_name text-dark"><?php echo htmlspecialchars($customer_name); ?></h5>
				</div>
			</div><!--.row-->
		</div><!--.container-->
		<div class="card-meta text-black px-2 py-2" style="background-color: #efefef">
			<div class="curbside_pickup_order_summary">$ <?php echo htmlspecialchars($order_total) ?> | <?php echo htmlspecialchars($item_count) ?> <?php echo (1 == $item_count) ? __('item', 'curbside-pickup') : __('items', 'curbside-pickup'); ?><?php if ( !empty($pickup_location_name) ) { echo ' | ' . htmlspecialchars($pickup_location_name); } ?></div>
		</div>
		<?php do_action('curbside_pickup_dashboard_card_after_meta', $pickup_id); ?>
		<ul class="list-group list-group-flush">
			<li class="list-group-item">
				<span class="card-label">Scheduled Time</span>
				<?php echo htmlspecialchars($scheduled_pickup_time); ?>
			</li>

			<?php if ( !empty($arrival_time) ): ?>
			<li class="list-group-item">
				<span class="card-label"><?php _e('Arrival Time', 'curbside-pickup'); ?>:</span>
				<?php echo htmlspecialchars($arrival_time); ?>
			</li>
			<?php endif; ?>

			<?php if ( !empty($space_number) ): ?>
			<li class="list-group-item">
				<span class="card-label"><?php _e('Space Number', 'curbside-pickup'); ?>:</span>
				<?php echo htmlspecialchars($space_number); ?>
			</li>
			<?php endif; ?>

			<?php if ( !empty($vehicle_description) ): ?>
			<li class="list-group-item">
				<span class="card-label"><?php _e('Vehicle Description', 'curbside-pickup'); ?>:</span>
				<?php echo htmlspecialchars($vehicle_description); ?>
			</li>
			<?php endif; ?>

			<?php if ( !empty($customer_notes) ): ?>
			<li class="list-group-item">
				<span class="card-label"><?php _e('Customer Notes', 'curbside-pickup'); ?>:</span>
				<?php echo htmlspecialchars($customer_notes); ?>
			</li>
			<?php endif; ?>

			<?php if ( !empty($internal_notes) ): ?>
			<li class="list-group-item">
				<span class="card-label"><?php _e('Internal Notes', 'curbside-pickup'); ?>:</span>
				<?php echo nl2br( htmlspecialchars($internal_notes) ); ?>
			</li>
			<?php endif; ?>
		</ul>
	</div>
</div>
