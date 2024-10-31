<div class="form-col-2">
  <div class="form-group">
    <label for="pos_address_1"><?php _e( 'Address 1', 'progy-multi-stores' ) ?></label>
    <input name="pos_address_1" id="pos_address_1" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_address_1', true ); ?>" class="">
  </div>
  <div class="form-group">
    <label for="pos_address_2"><?php _e( 'Address 2', 'progy-multi-stores' ) ?></label>
    <input name="pos_address_2" id="pos_address_2" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_address_2', true ); ?>" class="">
  </div>
</div>
<div class="form-col-2">
  <div class="form-group">
    <label for="pos_city"><?php _e( 'City', 'progy-multi-stores' ) ?></label>
    <input name="pos_city" id="pos_city" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_city', true ); ?>" class="">
  </div>
  <div class="form-group">
    <label for="pos_state"><?php _e( 'State/Province', 'progy-multi-stores' ) ?></label>
    <input type="text" name="pos_state" id="pos_state" value="<?php echo get_post_meta( $post->ID, 'pos_state', true ); ?>">
  </div>
</div>
<div class="form-col-2">
  <div class="form-group">
    <label for="pos_postcode"><?php _e( 'Postcode', 'progy-multi-stores' ) ?></label>
    <input name="pos_postcode" id="pos_postcode" type="text" value="<?php echo get_post_meta( $post->ID, 'pos_postcode', true ); ?>" class="small-input">
  </div>
  <div class="form-group">
    <label for="pos_country"><?php _e( 'Country', 'progy-multi-stores' ) ?></label>
    <select name="pos_country" id="pos_country">
      <?php $country = get_post_meta( $post->ID, '_country', true ) ? get_post_meta( $post->ID, 'pos_country', true ) : WC()->countries->get_base_country()?>
      <?php $countries = WC()->countries->get_countries(); ?>
      <?php foreach( $countries as $code => $name ): ?>
        <option value="<?php echo $code ?>" <?php if( $country == $code ) echo 'selected="selected"' ?>><?php echo $name ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<br class="clear">