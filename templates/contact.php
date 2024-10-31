<div class="clearfix">
	<div class="form-group">
		<label for="pos_url"><?php _e( 'Web Address' , 'progy-multi-stores') ?></label>
		<input name="pos_url" id="pos_url" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_url', true ); ?>" class="">
	</div>
	<div class="form-group">
		<label for="pos_phone"><?php _e( 'Telephone', 'progy-multi-stores' ) ?></label>
		<input name="pos_phone" id="pos_phone" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_phone', true ); ?>" class="">
	</div>
	<div class="form-group">
		<label for="pos_email"><?php _e( 'Email', 'progy-multi-stores') ?></label>
		<input name="pos_email" id="pos_email" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_email', true ); ?>" class="">
	</div>
</div>