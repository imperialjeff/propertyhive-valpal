<?php
$current_settings = get_option( 'propertyhive_valpal', array() );
?>
<form name="frmValPal" id="frmValPal" class="valpal-form" method="POST" action="">

    <?php if ( isset($current_settings['address_lookup']) && $current_settings['address_lookup'] == '1' ) { ?>

    <div id="postcode_control" class="control">
        <label for="postcode">Property Postcode</label>
        <input type="text" name="postcode" id="postcode" value="" required>
        <button id="find_address">Find Address</button>
    </div>
    <div id="address_results_control" class="control" style="display:none">
        <select id="address_results" name="property" required></select>
    </div>

    <?php }else{ ?>

    <div class="control">
        <label for="number">Property Name/Number <span class="required">*</span></label>
        <input type="text" name="number" id="number" value="" required>
    </div>

    <div class="control">
        <label for="street">Street <span class="required">*</span></label>
        <input type="text" name="street" id="street" value="" required>
    </div>

    <div class="control">
        <label for="postcode">Postcode <span class="required">*</span></label>
        <input type="text" name="postcode" id="postcode" value="" required>
    </div>

    <?php } ?>

    <div class="control">
        <label for="valuation_type">Valuation Type <span class="required">*</span></label>
        <select name="type" id="valuation_type" required>
            <?php if (empty($active_departments) || in_array('sales', $active_departments)) { ?>
                <option value="sales">Sales Valuation</option>
            <?php }
            if (empty($active_departments) || in_array('lettings', $active_departments)) { ?>
                <option value="lettings">Lettings Valuation</option>
            <?php } ?>
        </select> 
    </div>

    <div class="control">
        <label for="bedrooms">Bedrooms <span class="required">*</span></label>
        <select name="bedrooms" id="bedrooms" required>
            <option value="">Bedrooms</option>
            <option value="Studio">Studio</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="6+">6+</option>
        </select>
    </div>

    <div class="control">
        <label for="bedrooms">Property Type <span class="required">*</span></label>
        <select name="propertytype" required>
            <option value="">Property Type</option>
            <option value="Flat">Flat</option>
            <option value="Terraced House">Terraced House</option>
            <option value="Semi Detached House">Semi Detached House</option>
            <option value="Detached House">Detached House</option>
            <option value="Semi Detached Bungalow">Semi Detached Bungalow</option>
            <option value="Detached Bungalow">Detached Bungalow</option>
        </select>
    </div>

    <div class="control">
        <label for="name">Name <span class="required">*</span></label>
        <input type="text" name="name" id="name" value="" required> 
    </div>

    <div class="control">
        <label for="email">Email Address <span class="required">*</span></label>
        <input type="email" name="email" id="email" value="" required> 
    </div>

    <div class="control">
        <label for="telephone">Telephone Number <span class="required">*</span></label>
        <input type="text" name="telephone" id="telephone" value="" required> 
    </div>

    <div class="control">
        <label for="valpal_comments">Comments</label>
        <textarea name="comments" id="valpal_comments"></textarea>
    </div>

    <?php
    if ( isset($current_settings['valuation_form_disclaimer']) && $current_settings['valuation_form_disclaimer'] !== '' )
    {
        ?>
            <div class="control">
                <label style="width:100%;">
                    <input type="checkbox" name="disclaimer" id="disclaimer" value="1" required> <?php echo $current_settings['valuation_form_disclaimer']; ?> <span class="required">*</span>
                </label>
            </div>
        <?php
    }
    ?>

    <input type="submit" class="button" value="Get Instant Valuation">

    <div class="powered-by"><a href="https://www.valpal.co.uk/" rel="nofollow" target="_blank"></a></div>

</form>