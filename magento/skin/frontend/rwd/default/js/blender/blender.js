
var eliquid_category = [];

var custom_product_name = '';
var custom_pro_title = '';
var newtwitterurl = '';

eliquid_category['80'] = '#8B4513';		// Brown
eliquid_category['108'] = '#00FF00';	// Green
eliquid_category['115'] = '#FF3399'; 	// Light Pink
eliquid_category['90'] = '#3333FF';		// Light Blue
eliquid_category['106'] = '#FF3333';	// Light Red
eliquid_category['107'] = '#FFFF33';	// Light Yello
eliquid_category['109'] = '#00FFFF';	// Sky Blue
eliquid_category['112'] = '#808080';	// Gray
eliquid_category['116'] = '#FFFF00';

var price                     = '00.00';
var size                      = '';
var size_value                = '12ML';
var nicotine                  = '0MG - NO NICOTINE';
var nocotine_value            = '0MG';
var size_option_id            = '';
var size_option_type_id       = ''; 
var nicotine_option_id        = '';
var nicotine_option_type_id   = ''; 
var brand_name                = 'My Blend';
var blender_string            = '';
var blender_detail_string     = '';
var flv1                      = $j('#selected_flover').val();
var flv2                      = '';
var flv3                      = 'Tobaco Flover';
var ext_shot                  = 'Fruit Flover';
var flv1_per                  = '50%';
var flv2_per                  = '';
var flv3_per                  = '50%';
var flv1_id                   = '';
var flv2_id                   = '';
var flv3_id                   = '';
var ext_shot_id               = '';

$j('.brdtypenam').on('blur',function(){
	custom_product_name = $j('.brdtypenam').val();	
	$j("label[for='mycustombrand']").html(custom_product_name);
});

$j('#top_extra_size').css('background-color','#FFF');
$j('#top_extra_flvr').css('background-color','#FFF');
$j('#top_extra_nico').css('background-color','#FFF');
$j('#top_extra_shot').css('background-color','#FFF');
$j('#top_extra_preview').css('background-color','#FFF');

var productAddToCartForm = new VarienForm('product_addtocart_form');
productAddToCartForm.submit = function(button, url) {
    if (this.validator.validate()) {
        var form = this.form;
        var oldUrl = form.action;

        if (url) {
           form.action = url;
        }
        var e = null;
        try {
            this.form.submit();
        } catch (e) {
        }
        this.form.action = oldUrl;
        if (e) {
            throw e;
        }

        if (button && button != 'undefined') {
            button.disabled = true;
        }
    }
}.bind(productAddToCartForm);

productAddToCartForm.submitLight = function(button, url){
    if(this.validator) {
        var nv = Validation.methods;
        delete Validation.methods['required-entry'];
        delete Validation.methods['validate-one-required'];
        delete Validation.methods['validate-one-required-by-name'];
        // Remove custom datetime validators
        for (var methodName in Validation.methods) {
            if (methodName.match(/^validate-datetime-.*/i)) {
                delete Validation.methods[methodName];
            }
        }

        if (this.validator.validate()) {
            if (url) {
                this.form.action = url;
            }
            this.form.submit();
        }
        Object.extend(Validation.methods, nv);
    }
}.bind(productAddToCartForm);


function set_bottle_size(id){
    $j('.bottel_one').removeClass('selected-bottle');
    $j('.bottel_two').removeClass('selected-bottle');
    $j('.bottel_three').removeClass('selected-bottle');

    price = $j('#'+id).attr('price');
    size  = $j('#'+id).attr('size');
    size_option_id = $j('#'+id).attr('option-id');
    size_option_type_id = $j('#'+id).attr('option-type-id');
    size_value = $j('#'+id).attr('size')+'ML';
    
    $j('#form_option_size').attr('name','options['+size_option_id+']');
    $j('#form_option_size').val(size_option_type_id);

    $j('.bottel_'+id).addClass('selected-bottle');

    $j('.finished_main_price').html('$'+price);
    $j('#size_main_price').html('$'+price);
    $j('#size_nicotine_span').html(nicotine);
    $j('#size_size_span').html(size);
    $j('#flover_main_price').html('$'+price);
    $j('#finished_main_price').html('$'+price);
    $j('#flover_nicotine_span').html(nicotine);
    $j('#flover_size_span').html(size);
    $j('#nicotine_main_price').html('$'+price);
    $j('#nicotine_price').html('$'+price);
    $j('#nicotine_nicotine_span').html(nicotine);
    $j('#nicotine_size_span').html(size);
    $j('#shot_main_price').html('$'+price);
    $j('#shot_nicotine_span').html(nicotine);
    $j('#shot_size_span').html(size);
    return false;
}

function add_nicotin_level(mg){
    nicotine = $j('#'+mg).attr('lable');
    nicotine_option_id = $j('#'+mg).attr('option-id');
    nicotine_option_type_id = $j('#'+mg).attr('option-type-id');
    nocotine_value = mg;

    $j('.niocotine-color-div').removeClass('active-nicotine');
    $j('#div_'+mg).addClass('active-nicotine');
    $j('#form_option_nicotine').attr('name','options['+nicotine_option_id+']');
    $j('#form_option_nicotine').val(nicotine_option_type_id);

    $j('#li_'+mg).addClass('selected');
    $j('#nicotine_mg').html(mg);
    $j('#nicotine_nicotine_span').html(nicotine);
    $j('#flover_nicotine_span').html(nicotine);
    $j('#size_nicotine_span').html(nicotine);
    $j('#shot_nicotine_span').html(nicotine);
    $j('.nicotine_finished').html(nicotine);
    return false;
}

function custom_product_name_func(id){
    var custom_product = $j('#'+id).val();
    $j('#blend_image_name').html(custom_product);
    $j('#custom_product_name').val(custom_product);
    $j('#custom_product_name_flavors').val(custom_product);
    $j('#custom_product_name_nicotin').val(custom_product);
    $j('#custom_product_name_shot').val(custom_product);
    $j('.finished_custom_product_name').html(' ');
    $j('.finished_custom_product_name').html(custom_product);
    custom_pro_title = $j('#custom_product_name').val();
    /*var u = 'http://twitter.com/share?text=Currently reading http%3A%2F%2Fwww.siamcomm.com%2Fhow-tos%2Fadding-custom-sharing-buttons-for-facebook-twitter-and-linkedin-in-wordpress'
    var newtwitterurl = 'http://twitter.com/home?status= Product Title:'+custom_pro_title+' URL: '+inviteurl;
    $j('a.target').attr('href', u);*/
    /*$j("label[for='mycustombrand_flavor']").html(custom_product);
    $j("label[for='mycustombrand_nicotin']").html(custom_product);
    $j("label[for='mycustombrand_shot']").html(custom_product);*/
    brand_name = custom_product;
}

function set_extra_shot(id){
    ext_shot_id = id;
    cat_id = $j('#'+id).attr('cat-id');
    $j("#dropshoticon"+cat_id).css('top','20px');
    $j('.dropshoticon80').hide();
    $j('.dropshoticon90').hide();
    $j('.dropshoticon106').hide();
    $j('.dropshoticon107').hide();
    $j('.dropshoticon108').hide();
    $j('.dropshoticon109').hide();
    $j('.dropshoticon112').hide();
    $j('.dropshoticon115').hide();
    $j('.dropshoticon116').hide();
    
    ext_shot = $j('#'+id).attr('product');

    $j('.flavor-shot-img').removeClass('newmousepointer'); 
    $j('.'+id+'_div').addClass('newmousepointer');


    if(id=='no_shot'){
        $j('.transparant-bottel3').hide();
        $j('.drop_span').hide();
        $j('.shot_finished').hide();
        $j('.dropshoticon'+id).hide();

        $j('#top_extra_size').css('background-color','#FFF');
        $j('#top_extra_flvr').css('background-color','#FFF');
        $j('#top_extra_nico').css('background-color','#FFF');
        $j('#top_extra_shot').css('background-color','#FFF');
        $j('#top_extra_preview').css('background-color','#FFF');
    }
    else {
        $j('#shot_drop_span').html(' '+ext_shot);
        $j('.drop_span').show();

        $j('.shot_finished_product_name').html(' Shot ('+ext_shot+')');
        $j('.shot_finished').show();

        $j('.transparant-bottel3').show();
        $j('.dropshoticon'+cat_id).show();

        $j("#dropshoticon"+cat_id).animate({top: '120px',opacity: '0.4'},'slow');

        setTimeout(function(){ 
            $j('.transparant-bottel3').hide(); 
            showTopDrop(cat_id);
        }, 800);

        setTimeout(function(){ 
            $j('.dropshoticon'+cat_id).hide(); 
            $j("#dropshoticon"+cat_id).css('top','20px');
        }, 800);
    }

    return false;
}

function showTopDrop(id){
    $j('#top_extra_size').css('background-color',eliquid_category[id]);
    $j('#top_extra_flvr').css('background-color',eliquid_category[id]);
    $j('#top_extra_nico').css('background-color',eliquid_category[id]);
    $j('#top_extra_shot').css('background-color',eliquid_category[id]);
    $j('#top_extra_preview').css('background-color',eliquid_category[id]);
}

function add_to_cart(ele){
        
    /*Blend Name : XYZ,  Flavor 1 : Graps (10%), Flavor 2 : Nicotine (90%)*/
    flv1_per = $j('#flaver1_per').val();
    flv2_per = $j('#flaver2_per').val();
    flv3_per = $j('#flaver3_per').val();

    if(flv1_id != ''){
        blender_detail_string += flv1_id+'-'+flv1_per+',';
    }
    if(flv2_id != ''){
        blender_detail_string += flv2_id+'-'+flv2_per+',';
    }
    if(flv3_id != ''){
        blender_detail_string += flv3_id+'-'+flv3_per;
    }

    if(ext_shot_id != ''){
        blender_detail_string += '/ Shot:'+ext_shot_id;
    }

    if(brand_name != ''){
        blender_detail_string += '/ Blend:'+brand_name;
        blender_string = 'Blend Name : '+brand_name;
    }

    var flavors = ', Flavors : ';
    if(flv1 != ''){
        flavors += '1. '+ flv1+' ('+flv1_per+')';   
    }
    if(flv2 != ''){
        flavors += ' 2. '+flv2+' ('+flv2_per+')';
    }
    if(flv3 != ''){
        flavors += ' 3. '+flv3+' ('+flv3_per+')';
    }

    // if(ext_shot != ''){
    //     blender_string += ' and with extra shot of '+ext_shot;   
    // }
    blender_string += flavors;
    
    if(ext_shot != ''){
        blender_string += ', Extra Shot : '+ext_shot;
    }

    $j('#form_detail').val(blender_detail_string);
    $j('#form_description').val(blender_string);
    productAddToCartForm.submit(ele);
}

function callMarkImageFunc(id){
    var checkboxcheckedlenght = $j('input[name="flavour[]"]:checked').length;
    var index = parseInt(id);
    if(checkboxcheckedlenght >2){
        /*$j('#flv' + index).priceop('checked', false);*/

        if(!$j("#"+index+'flvforeimage').is(':visible')){
            alert('Please select up to three flavours');
            return false;    
        }
        else{
            
            var checked = $j('.' + index + 'check').eq(0).is(':checked');
            $j('.' + index + 'check').prop('checked', !checked);
            $j("#"+index+'flvforeimage').toggle();
            callBottleFillUpDown(index);    
        }
        
    }
    else{
        
        var checked = $j('.' + index + 'check').eq(0).is(':checked');
        $j('.' + index + 'check').prop('checked', !checked);
        $j("#"+index+'flvforeimage').toggle();
        callBottleFillUpDown(index);    
    }
}

function callUnMarkImageFunc(id){
    
    var index = parseInt(id);
    var checked = $j('.' + index + 'check').eq(0).is(':checked');
    /*$j('.' + index + 'check').prop('checked', !checked);*/
    $j('#flv' + index).prop('checked', !checked);
    $j("#"+index+'flvforeimage').hide();
    callBottleFillUpDown(index);
}

function callCloseFlavour(id){
    var checkboxcheckedlen = $j('input[name="flavour[]"]:checked').length;
    /*if(checkboxcheckedlen == 3){
        $j('#flaver1_per').val('50%');
        $j('#flaver2_per').val('50%');
        $j('#flaver3_per').val('');
    }
    if(checkboxcheckedlen == 2){
        $j('#flaver1_per').val('100%');
        $j('#flaver2_per').val('');
        $j('#flaver3_per').val('');   
    }*/

    var totalproduct = 0;
    $j('.flv').each(function() { //loop through each checkbox
        var flg = $j('#'+this.id).prop("checked");
        if(flg==true){
            totalproduct++;
        }
    });

    $j('#outsidediv_size').remove();
    $j('#outsidediv').remove();
    $j('#outsidediv_nico').remove();
    $j('#outsidediv_shot').remove();
    $j('#outsidediv_finished_last').remove();
    
    $j('#innerdiv_size').remove();
    $j('#innerdiv').remove();
    $j('#innerdiv_nico').remove();
    $j('#innerdiv_shot').remove();
    $j('#innerdiv_finished_last').remove();

    $j('#subinnerdiv_size').remove();
    $j('#subinnerdiv').remove();
    $j('#subinnerdiv_nico').remove();
    $j('#subinnerdiv_shot').remove();
    $j('#subinnerdiv_finished_last').remove();

    $j('#choose_size_fl1').hide();
    $j('#choose_size_fl2').hide();
    $j('#choose_size_fl3').hide();

    $j('#choose_flavor_fl1').hide();
    $j('#choose_flavor_fl2').hide();
    $j('#choose_flavor_fl3').hide();

    $j('#choose_nicotin_fl1').hide();
    $j('#choose_nicotin_fl2').hide();
    $j('#choose_nicotin_fl3').hide();

    $j('#choose_shot_fl1').hide();
    $j('#choose_shot_fl2').hide();
    $j('#choose_shot_fl3').hide();

    $j('.finished_flv1').hide();
    $j('.finished_flv2').hide();
    $j('.finished_flv3').hide();

    $j('#draggable').hide();
    $j('#draggable2').hide();

    /*alert(' id= '+id+' total product = '+totalproduct);return false;*/

    if(id==1){
        flv1_id = flv2_id;
        flv2_id = flv3_id;
        flv3_id = '';
        if(totalproduct==1){
            $j('#flaver1_per').val('');
            $j('#flaver2_per').val('');
            $j('#flaver3_per').val('');
            var id = parseInt(id);

            $j('.flv').each(function() { //loop through each checkbox
                this.checked = false;
            });

            var imgcnt = 0;
            $j('.correctimg').each(function() { //loop through each checkbox
                $j("#"+imgcnt+'flvforeimage').hide();
                imgcnt++;
            });

            flv1 = '';
            flv2 = '';
            flv3 = '';
        }
        else if(totalproduct==2){
            $j('#flaver1_per').val('100%');
            $j('#flaver2_per').val('');
            $j('#flaver3_per').val('');
            flv1 = '';
            flv2 = '';
            flv3 = '';

            var id = parseInt(id);

            var temp = 0;
            var fullcnt = 1;
            var flvclrcnt = 0;

            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(temp==0){
                        this.checked = false;
                        $j("#"+fullcnt+'flvforeimage').hide();
                        temp++;
                    }
                    else if(temp==1){
                        
                        flag = 0;

                        $j('.flv').each(function() { //loop through each checkbox
                            var flg = $j('#'+this.id).prop("checked");
                            if(flg==true){
                                flvcolor = $j('#flv'+flvclrcnt).attr('color');
                                firstprocolor = flvcolor;
                                $j('<div>', { 
                                    id: 'outsidediv_size'
                                }).appendTo('#sub_block_2_size');
                                $j('#outsidediv_size').addClass('new_outsidedivsingle');
                                $j('#outsidediv_size').css('background-color',flvcolor);

                                $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                                $j('#choose_size_fl1').show();

                                $j('<div>', { 
                                    id: 'outsidediv'
                                }).appendTo('#sub_block_2');
                                $j('#outsidediv').addClass('new_outsidedivsingle');
                                $j('#outsidediv').css('background-color',flvcolor);

                                $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                                $j('#choose_flavor_fl1').show();


                                $j('<div>', { 
                                    id: 'outsidediv_nico'
                                }).appendTo('#sub_block_2_nico');
                                $j('#outsidediv_nico').addClass('new_outsidedivsingle');
                                $j('#outsidediv_nico').css('background-color',flvcolor);

                                $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                                $j('#choose_nicotin_fl1').show();

                                flv1 = $j('#flv'+flvclrcnt).attr('title');

                                $j('<div>', { 
                                    id: 'outsidediv_finished_last'
                                }).appendTo('#sub_block_2_finished_last');
                                $j('#outsidediv_finished_last').addClass('new_outsidedivsingle');
                                $j('#outsidediv_finished_last').css('background-color',flvcolor);

                                $j('.finished_flv1').show();
                                $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (100%)');  

                                $j('<div>', { 
                                    id: 'outsidediv_shot'
                                }).appendTo('#sub_block_2_shot');
                                $j('#outsidediv_shot').addClass('new_outsidedivsingle');
                                $j('#outsidediv_shot').css('background-color',flvcolor);

                                $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                                $j('#choose_shot_fl1').show();

                                $j('#choose_size_percentage_fl1').html(' Equal Mix');
                                $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                                $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                                $j('#choose_shot_percentage_fl1').html(' Equal Mix');
                            }
                        });
                    }
                }
                fullcnt++;
            });
        } // end of total two product 
        else if(totalproduct==3){
            $j('#flaver1_per').val('50%');
            $j('#flaver2_per').val('50%');
            $j('#flaver3_per').val('');
            var id = parseInt(id);

            var cnt=mgsize=nicotinesize= 0;
            var temp = 0;
            var fullcnt = 1;
            var flvclrcnt = 0;
            flv1 = '';
            flv2 = '';
            flv3 = '';

            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(temp==0){
                        this.checked = false;
                        $j("#"+fullcnt+'flvforeimage').hide();
                        temp++;
                    }
                    else if(temp==1){

                        $j('#draggable').show();
                        $j('#draggable').css('top','170px');
                        $j('#draggable2').hide();

                        flvcolor = $j('#flv'+flvclrcnt).attr('color');
                        /*alert(' new color: '+flvcolor+' second pro color: '+secondprocolor);return false;
                        if(secondprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }*/
                        firstprocolor = flvcolor;
                        //alert(firstprocolor);
                        $j('<div>', { 
                            id: 'outsidediv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#outsidediv_size').addClass('new_outsidedivdouble');
                        $j('#outsidediv_size').css('background-color',flvcolor); 

                        $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_size_fl1').show();

                        $j('<div>', { 
                            id: 'outsidediv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#outsidediv').addClass('new_outsidedivdouble');
                        $j('#outsidediv').css('background-color',flvcolor); 

                        $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_flavor_fl1').show();

                        $j('<div>', { 
                            id: 'outsidediv_nico'
                        }).appendTo('#sub_block_2_nico');
                        
                        $j('#outsidediv_nico').addClass('new_outsidedivdouble');
                        $j('#outsidediv_nico').css('background-color',flvcolor); 

                        $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_nicotin_fl1').show();                        

                        $j('<div>', { 
                            id: 'outsidediv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#outsidediv_shot').addClass('new_outsidedivdouble');
                        $j('#outsidediv_shot').css('background-color',flvcolor); 

                        $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_shot_fl1').show();



                        $j('<div>', { 
                            id: 'outsidediv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        
                        $j('#outsidediv_finished_last').addClass('new_outsidedivdouble');
                        $j('#outsidediv_finished_last').css('background-color',flvcolor); 

                        $j('.finished_flv1').show();
                        $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+ ' (50%)');

                        $j('#choose_size_percentage_fl1').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl1').html(' Equal Mix');

                        flv1 = $j('#flv'+flvclrcnt).attr('title');                    
                        /*flv1_per = '100%';*/
                        temp++;                 
                    }
                    else if(temp==2){

                        flvcolor = $j('#flv'+flvclrcnt).attr('color');
                        if(firstprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }
                        secondprocolor = flvcolor;

                        $j('<div>', {
                            id: 'innerdiv_size'
                        }).appendTo('#sub_block_2_size');
        

                        $j('#innerdiv_size').addClass('new_innerdivdouble');
                        $j('#innerdiv_size').css('background-color',flvcolor);                      

                        $j('#choose_size_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_size_fl2').show();                     


                        $j('<div>', {
                            id: 'innerdiv'
                        }).appendTo('#sub_block_2');

                        $j('#innerdiv').addClass('new_innerdivdouble');
                        $j('#innerdiv').css('background-color',flvcolor);                      

                        $j('#choose_flavor_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_flavor_fl2').show();                     



                        $j('<div>', {
                            id: 'innerdiv_nico'
                        }).appendTo('#sub_block_2_nico');
        

                        $j('#innerdiv_nico').addClass('new_innerdivdouble');
                        $j('#innerdiv_nico').css('background-color',flvcolor);                      

                        $j('#choose_nicotin_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_nicotin_fl2').show();                     


                        $j('<div>', {
                            id: 'innerdiv_shot'
                        }).appendTo('#sub_block_2_shot');

                        $j('#innerdiv_shot').addClass('new_innerdivdouble');
                        $j('#innerdiv_shot').css('background-color',flvcolor);                      

                        $j('#choose_shot_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_shot_fl2').show();



                        $j('<div>', {
                            id: 'innerdiv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');

                        $j('#innerdiv_finished_last').addClass('new_innerdivdouble');
                        $j('#innerdiv_finished_last').css('background-color',flvcolor);                      

                        $j('.finished_flv2').show();
                        $j('.finished_flv2').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');    

                        $j('#choose_size_percentage_fl2').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl2').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl2').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl2').html(' Equal Mix');

                        flv2 = $j('#flv'+flvclrcnt).attr('title');                 
                        /*flv1_per = '100%';
                        flv2_per = '100%';*/
                        temp++;
                    }
                }
                fullcnt++;
            }); 
        } // end of total three product
    } // // end of else if id 1
    else if(id==2){
        flv2_id = flv3_id;
        flv3_id = '';
        if(totalproduct==2){
            $j('#flaver1_per').val('100%');
            $j('#flaver2_per').val('');
            $j('#flaver3_per').val('');   
            flv1 = '';
            flv2 = '';
            flv3 = '';

            var id = parseInt(id);
            var fullcnt = 1;
            var temp = 0;
            var flvclrcnt = 0;

            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(temp==0){
                        flvcolor = $j('#flv'+flvclrcnt).attr('color');
                        firstprocolor = flvcolor;

                        $j('<div>', { 
                            id: 'outsidediv_size'
                        }).appendTo('#sub_block_2_size');
                        $j('#outsidediv_size').addClass('new_outsidedivsingle');
                        $j('#outsidediv_size').css('background-color',flvcolor);

                        $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_size_fl1').show();

                        $j('<div>', { 
                            id: 'outsidediv'
                        }).appendTo('#sub_block_2');
                        $j('#outsidediv').addClass('new_outsidedivsingle');
                        $j('#outsidediv').css('background-color',flvcolor);

                        $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_flavor_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#outsidediv_nico').addClass('new_outsidedivsingle');
                        $j('#outsidediv_nico').css('background-color',flvcolor);

                        $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_nicotin_fl1').show();

                        
                        $j('<div>', { 
                            id: 'outsidediv_shot'
                        }).appendTo('#sub_block_2_shot');
                        $j('#outsidediv_shot').addClass('new_outsidedivsingle');
                        $j('#outsidediv_shot').css('background-color',flvcolor);

                        $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_shot_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');

                        $j('#outsidediv_finished_last').addClass('new_outsidedivsingle');
                        $j('#outsidediv_finished_last').css('background-color',flvcolor); 

                        $j('.finished_flv1').show();
                        $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title') +' (100%)');

                        $j('#choose_size_percentage_fl1').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl1').html(' Equal Mix');

                        flv1 = $j('#flv'+flvclrcnt).attr('title');

                        temp++;
                    }
                    else if(temp==1){
                        this.checked = false;
                        $j("#"+fullcnt+'flvforeimage').hide();
                        temp++;
                    }
                }
                fullcnt++;
            });
        } // end of total two product 
        else if(totalproduct==3){
            $j('#flaver1_per').val('50%');
            $j('#flaver2_per').val('50%');
            $j('#flaver3_per').val('');   
            flv1 = '';
            flv2 = '';
            flv3 = '';

            var id = parseInt(id);
            var fullcnt = 1;
            var cnt=mgsize=nicotinesize= 0;
            var temp = 0;
            var flvclrcnt = 0;

            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(temp==0){

                        $j('#draggable').show();
                        $j('#draggable').css('top','170px');
                        $j('#draggable2').hide();

                        flvcolor = $j('#flv'+flvclrcnt).attr('color');

                        if(secondprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }

                        firstprocolor = flvcolor;

                        $j('<div>', { 
                            id: 'outsidediv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#outsidediv_size').addClass('new_outsidedivdouble');
                        $j('#outsidediv_size').css('background-color',flvcolor); 

                        $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_size_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#outsidediv').addClass('new_outsidedivdouble');
                        $j('#outsidediv').css('background-color',flvcolor); 

                        $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_flavor_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv_nico'
                        }).appendTo('#sub_block_2_nico');
                        
                        $j('#outsidediv_nico').addClass('new_outsidedivdouble');
                        $j('#outsidediv_nico').css('background-color',flvcolor); 

                        $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_nicotin_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#outsidediv_shot').addClass('new_outsidedivdouble');
                        $j('#outsidediv_shot').css('background-color',flvcolor); 

                        $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_shot_fl1').show();


                        $j('<div>', { 
                            id: 'outsidediv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');

                        $j('#outsidediv_finished_last').addClass('new_outsidedivdouble');
                        $j('#outsidediv_finished_last').css('background-color',flvcolor); 

                        $j('.finished_flv1').show();
                        $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');

                        $j('#choose_size_percentage_fl1').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl1').html(' Equal Mix');

                        flv1 = $j('#flv'+flvclrcnt).attr('title');

                        temp++;                 
                    }
                    else if(temp==1){
                        this.checked = false;
                        $j("#"+fullcnt+'flvforeimage').hide();
                        temp++;
                    }
                    else if(temp==2){
                        flvcolor = $j('#flv'+flvclrcnt).attr('color');

                        if(firstprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }

                        secondprocolor = flvcolor;

                        $j('<div>', {
                            id: 'innerdiv_size'
                        }).appendTo('#sub_block_2_size');

                        $j('#innerdiv_size').addClass('new_innerdivdouble');
                        $j('#innerdiv_size').css('background-color',flvcolor);                      

                        $j('#choose_size_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_size_fl2').show();


                        $j('<div>', {
                            id: 'innerdiv'
                        }).appendTo('#sub_block_2');

                        $j('#innerdiv').addClass('new_innerdivdouble');
                        $j('#innerdiv').css('background-color',flvcolor);                      

                        $j('#choose_flavor_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_flavor_fl2').show();                     


                        $j('<div>', {
                            id: 'innerdiv_nico'
                        }).appendTo('#sub_block_2_nico');

                        $j('#innerdiv_nico').addClass('new_innerdivdouble');
                        $j('#innerdiv_nico').css('background-color',flvcolor);                      

                        $j('#choose_nicotin_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_nicotin_fl2').show();                     


                        $j('<div>', {
                            id: 'innerdiv_shot'
                        }).appendTo('#sub_block_2_shot');

                        $j('#innerdiv_shot').addClass('new_innerdivdouble');
                        $j('#innerdiv_shot').css('background-color',flvcolor);                      

                        $j('#choose_shot_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                        $j('#choose_shot_fl2').show();

                         $j('<div>', {
                            id: 'innerdiv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');

                        $j('#innerdiv_finished_last').addClass('new_innerdivdouble');
                        $j('#innerdiv_finished_last').css('background-color',flvcolor);                      

                        $j('.finished_flv2').show();
                        $j('.finished_flv2').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');

                        $j('#choose_size_percentage_fl2').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl2').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl2').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl2').html(' Equal Mix');

                        flv2 = $j('#flv'+flvclrcnt).attr('title');

                        temp++;
                    }
                }
                fullcnt++;
            }); 
        }
    } // // end of else if id 2
    else if(id==3){
        flv3_id = '';
        flv1 = '';
        flv2 = '';
        flv3 = '';
        $j('#flaver1_per').val('50%');
        $j('#flaver2_per').val('50%');
        $j('#flaver3_per').val('');
        var id = parseInt(id);
        var fullcnt = 1;
        var cnt=mgsize=nicotinesize= 0;
        var temp = 0;
        var flvclrcnt = 0;

        $j('.flv').each(function() { //loop through each checkbox
            flvclrcnt++;
            var flg = $j('#'+this.id).prop("checked");
            if(flg==true){
                if(temp==0){

                    $j('#draggable').show();
                    $j('#draggable').css('top','170px');
                    $j('#draggable2').hide();

                    flvcolor = $j('#flv'+flvclrcnt).attr('color');

                    if(secondprocolor == flvcolor){
                        flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                    }

                    firstprocolor = flvcolor;

                    $j('<div>', { 
                        id: 'outsidediv_size'
                    }).appendTo('#sub_block_2_size');
                    
                    $j('#outsidediv_size').addClass('new_outsidedivdouble');
                    $j('#outsidediv_size').css('background-color',flvcolor); 

                    $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_size_fl1').show();


                    $j('<div>', { 
                        id: 'outsidediv'
                    }).appendTo('#sub_block_2');
                    
                    $j('#outsidediv').addClass('new_outsidedivdouble');
                    $j('#outsidediv').css('background-color',flvcolor); 

                    $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_flavor_fl1').show();


                    $j('<div>', { 
                        id: 'outsidediv_nico'
                    }).appendTo('#sub_block_2_nico');
                    
                    $j('#outsidediv_nico').addClass('new_outsidedivdouble');
                    $j('#outsidediv_nico').css('background-color',flvcolor); 

                    $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_nicotin_fl1').show();

                    $j('<div>', { 
                        id: 'outsidediv_shot'
                    }).appendTo('#sub_block_2_shot');
                    
                    $j('#outsidediv_shot').addClass('new_outsidedivdouble');
                    $j('#outsidediv_shot').css('background-color',flvcolor); 

                    $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_shot_fl1').show();


                    $j('<div>', { 
                        id: 'outsidediv_finished_last'
                    }).appendTo('#sub_block_2_finished_last');

                    $j('#outsidediv_finished_last').addClass('new_outsidedivdouble');
                    $j('#outsidediv_finished_last').css('background-color',flvcolor); 

                    $j('.finished_flv1').show();
                    $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');

                    $j('#choose_size_percentage_fl1').html(' Equal Mix');
                    $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                    $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                    $j('#choose_shot_percentage_fl1').html(' Equal Mix');

                    flv1 = $j('#flv'+flvclrcnt).attr('title');

                    temp++;                 
                }
                else if(temp==1){

                    flvcolor = $j('#flv'+flvclrcnt).attr('color');

                    if(firstprocolor == flvcolor){
                        flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                    }

                    secondprocolor = flvcolor;

                    $j('<div>', {
                        id: 'innerdiv_size'
                    }).appendTo('#sub_block_2_size');

                    $j('#innerdiv_size').addClass('new_innerdivdouble');
                    $j('#innerdiv_size').css('background-color',flvcolor);                      

                    $j('#choose_size_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_size_fl2').show();                                         


                    $j('<div>', {
                        id: 'innerdiv'
                    }).appendTo('#sub_block_2');

                    $j('#innerdiv').addClass('new_innerdivdouble');
                    $j('#innerdiv').css('background-color',flvcolor);                      

                    $j('#choose_flavor_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_flavor_fl2').show();                                         

                    $j('<div>', {
                        id: 'innerdiv_nico'
                    }).appendTo('#sub_block_2_nico');

                    $j('#innerdiv_nico').addClass('new_innerdivdouble');
                    $j('#innerdiv_nico').css('background-color',flvcolor);                      

                    $j('#choose_nicotin_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_nicotin_fl2').show();                                         

                    $j('<div>', {
                        id: 'innerdiv_shot'
                    }).appendTo('#sub_block_2_shot');

                    $j('#innerdiv_shot').addClass('new_innerdivdouble');
                    $j('#innerdiv_shot').css('background-color',flvcolor);                      

                    $j('#choose_shot_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));                      
                    $j('#choose_shot_fl2').show();     


                     $j('<div>', {
                        id: 'innerdiv_finished_last'
                    }).appendTo('#sub_block_2_finished_last');

                    $j('#innerdiv_finished_last').addClass('new_innerdivdouble');
                    $j('#innerdiv_finished_last').css('background-color',flvcolor);                      

                    $j('.finished_flv2').show();
                    $j('.finished_flv2').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');

                    $j('#choose_size_percentage_fl2').html(' Equal Mix');
                    $j('#choose_flavor_percentage_fl2').html(' Equal Mix');
                    $j('#choose_nicotin_percentage_fl2').html(' Equal Mix');
                    $j('#choose_shot_percentage_fl2').html(' Equal Mix');

                    flv2 = $j('#flv'+flvclrcnt).attr('title');                                    

                    temp++;
                }   
                else if(temp==2){
                    this.checked = false;
                    $j("#"+fullcnt+'flvforeimage').hide();
                    temp++;
                }
            }
            fullcnt++;
        }); 
    } // end of else if id 3
} // end of function

function callBottleFillUpDown(id){
    flv1 = '';
    flv2 = '';
    flv3 = '';
    flv1_id = '';
    flv2_id = '';
    flv3_id = '';

    $j('.finished_flv1').hide();
    $j('.finished_flv2').hide();
    $j('.finished_flv3').hide();

    var checkboxcheckedlen = $j('input[name="flavour[]"]:checked').length;
    //Percentage Selection
    if(checkboxcheckedlen == 1){
        $j('#flaver1_per').val('100%');
    }
    else if(checkboxcheckedlen == 2){
        $j('#flaver1_per').val('50%');
        $j('#flaver2_per').val('50%');
    }
    else if(checkboxcheckedlen == 3){
        $j('#flaver1_per').val('33%');
        $j('#flaver2_per').val('33%');
        $j('#flaver3_per').val('33%');
    }
    // End Percentage Selection

    if(checkboxcheckedlen<=3){

        $j('#outsidediv_size').remove();
        $j('#outsidediv').remove();
        $j('#outsidediv_nico').remove();
        $j('#outsidediv_shot').remove();
        $j('#outsidediv_finished_last').remove();

        $j('#innerdiv_size').remove();
        $j('#innerdiv').remove();
        $j('#innerdiv_nico').remove();
        $j('#innerdiv_shot').remove();
        $j('#innerdiv_finished_last').remove();

        $j('#subinnerdiv_size').remove();
        $j('#subinnerdiv').remove();
        $j('#subinnerdiv_nico').remove();
        $j('#subinnerdiv_shot').remove();
        $j('#subinnerdiv_finished_last').remove();

        if(checkboxcheckedlen==1){

            $j('#draggable').hide();
            $j('#draggable2').hide();
            var flvclrcnt = 0;

            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){

                    firstcolorid = flvclrcnt;

                    flvcolor = $j('#flv'+flvclrcnt).attr('color');

                    firstprocolor = flvcolor;

                    $j('<div>', { 
                        id: 'outsidediv_size'
                    }).appendTo('#sub_block_2_size');
                    $j('#outsidediv_size').addClass('new_outsidedivsingle');
                    $j('#outsidediv_size').css('background-color',flvcolor);

                    $j('<div>', { 
                        id: 'outsidediv'
                    }).appendTo('#sub_block_2');
                    $j('#outsidediv').addClass('new_outsidedivsingle');
                    $j('#outsidediv').css('background-color',flvcolor);

                    $j('<div>', { 
                        id: 'outsidediv_nico'
                    }).appendTo('#sub_block_2_nico');
                    $j('#outsidediv_nico').addClass('new_outsidedivsingle');
                    $j('#outsidediv_nico').css('background-color',flvcolor);

                    $j('<div>', { 
                        id: 'outsidediv_shot'
                    }).appendTo('#sub_block_2_shot');
                    $j('#outsidediv_shot').addClass('new_outsidedivsingle');
                    $j('#outsidediv_shot').css('background-color',flvcolor);

                    $j('<div>', { 
                        id: 'outsidediv_finished_last'
                    }).appendTo('#sub_block_2_finished_last');
                    $j('#outsidediv_finished_last').addClass('new_outsidedivsingle');
                    $j('#outsidediv_finished_last').css('background-color',flvcolor);

                    $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                    $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                    $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                    $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                    $j('.finished_flv1').show();
                    $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (100%)');

                    flv1 = $j('#flv'+flvclrcnt).attr('title');
                    flv1_id = $j('#flv'+flvclrcnt).attr('product-id');

                    $j('#choose_size_fl1').show();
                    $j('#choose_flavor_fl1').show();
                    $j('#choose_nicotin_fl1').show();
                    $j('#choose_shot_fl1').show();

                    $j('#choose_flavor_fl2').hide();
                    $j('#choose_flavor_fl3').hide();

                    $j('#choose_nicotin_fl2').hide();
                    $j('#choose_nicotin_fl3').hide();

                    $j('#choose_shot_fl2').hide();
                    $j('#choose_shot_fl3').hide();

                    $j('#choose_size_percentage_fl1').html(' Equal Mix');
                    $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                    $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                    $j('#choose_shot_percentage_fl1').html(' Equal Mix');
                }
            });

            $j('#firstper').html('100%');
        }
        else if(checkboxcheckedlen==2){

            $j('#draggable').show();
            $j('#draggable').css('top','170px');
            $j('#draggable2').hide();

            flag = 0;

            $j('#choose_flavor_fl1').hide();
            $j('#choose_flavor_fl2').hide();
            $j('#choose_flavor_fl3').hide();

            var cnt=mgsize=nicotinesize= 0;
            var flvclrcnt = 0;
            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(cnt==0){
                        firstcolorid = flvclrcnt;

                        $j('#subinnerdiv').remove();
                        flvcolor = $j('#flv'+flvclrcnt).attr('color');
                        firstprocolor = flvcolor;

                        $j('<div>', { 
                            id: 'outsidediv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#outsidediv_size').addClass('new_outsidedivdouble');
                        $j('#outsidediv_size').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'outsidediv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#outsidediv').addClass('new_outsidedivdouble');
                        $j('#outsidediv').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'outsidediv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#outsidediv_nico').addClass('new_outsidedivdouble');
                        $j('#outsidediv_nico').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'outsidediv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        $j('#outsidediv_finished_last').addClass('new_outsidedivdouble');
                        $j('#outsidediv_finished_last').css('background-color',flvcolor);

                        $j('<div>', {
                            id: 'outsidediv_shot'
                        }).appendTo('#sub_block_2_shot');
                        $j('#outsidediv_shot').addClass('new_outsidedivdouble');
                        $j('#outsidediv_shot').css('background-color',flvcolor);

                        $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('.finished_flv1').show();
                        $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');

                        flv1 = $j('#flv'+flvclrcnt).attr('title');
                        flv1_id = $j('#flv'+flvclrcnt).attr('product-id');

                        $j('#choose_size_fl1').show();
                        $j('#choose_flavor_fl1').show();
                        $j('#choose_nicotin_fl1').show();
                        $j('#choose_shot_fl1').show();

                        $j('#choose_size_percentage_fl1').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl1').html(' Equal Mix');
                        flv1_per = '50%';
                        flv2_per = '50%';
                    }
                    else {
                        secondcolorid = flvclrcnt;
                        
                        flvcolor = $j('#flv'+flvclrcnt).attr('color');

                        if(firstprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }

                        secondprocolor = flvcolor;

                        $j('<div>', {
                            id: 'innerdiv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#innerdiv_size').addClass('new_innerdivdouble');
                        $j('#innerdiv_size').css('background-color',flvcolor);

                        $j('<div>', {
                            id: 'innerdiv'
                        }).appendTo('#sub_block_2');

                        $j('#innerdiv').addClass('new_innerdivdouble');
                        $j('#innerdiv').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'innerdiv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#innerdiv_nico').addClass('new_innerdivdouble');
                        $j('#innerdiv_nico').css('background-color',flvcolor);

                        
                        $j('<div>', { 
                            id: 'innerdiv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        
                        $j('#innerdiv_finished_last').addClass('new_innerdivdouble');
                        $j('#innerdiv_finished_last').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'innerdiv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#innerdiv_shot').addClass('new_innerdivdouble');
                        $j('#innerdiv_shot').css('background-color',flvcolor);                        

                        $j('#choose_size_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_flavor_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_nicotin_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_shot_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('.finished_flv2').show();
                        $j('.finished_flv2').html($j('#flv'+flvclrcnt).attr('title')+' (50%)');  

                        flv2 = $j('#flv'+flvclrcnt).attr('title');                      
                        flv2_id = $j('#flv'+flvclrcnt).attr('product-id');                      

                        $j('#choose_size_fl2').show();  
                        $j('#choose_flavor_fl2').show();  
                        $j('#choose_nicotin_fl2').show();
                        $j('#choose_shot_fl2').show();  

                        $j('#choose_size_percentage_fl2').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl2').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl2').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl2').html(' Equal Mix');                   
                    }
                    cnt++;
                }
            });

            $j('#mgsize').html(mgsize);
            $j('#nicotinesize').html(nicotinesize);

            // code for canvas
            var totalheight = 0;
            var firstblockheight = parseInt($j("#outsidediv").height());
            var secondblockheight = parseInt($j("#innerdiv").height());
            totalheight = firstblockheight + secondblockheight;
            // end of code for canvas
        }
        else if(checkboxcheckedlen==3){

            $j('#draggable').show();
            $j('#draggable2').show();

            $j('#draggable').css('top','112px');
            $j('#draggable2').css('top','198px');

            $j('#choose_flavor_fl1').hide();
            $j('#choose_flavor_fl2').hide();
            $j('#choose_flavor_fl3').hide();

            var cnt=mgsize=nicotinesize= 0;
            var flvclrcnt = 0;
            $j('.flv').each(function() { //loop through each checkbox
                flvclrcnt++;
                var flg = $j('#'+this.id).prop("checked");
                if(flg==true){
                    if(cnt==0){
                        firstcolorid = flvclrcnt;

                        $j('#outsidediv_size').remove();
                        $j('#outsidediv').remove();
                        $j('#outsidediv_nico').remove();
                        $j('#outsidediv_shot').remove();
                        $j('#outsidediv_finished_last').remove();

                        $j('#innerdiv_size').remove();
                        $j('#innerdiv').remove();
                        $j('#innerdiv_nico').remove();
                        $j('#innerdiv_shot').remove();
                        $j('#innerdiv_finished_last').remove();

                        $j('#subinnerdiv_size').remove();
                        $j('#subinnerdiv').remove();
                        $j('#subinnerdiv_nico').remove();
                        $j('#subinnerdiv_shot').remove();
                        $j('#subinnerdiv_finished_last').remove();


                        flvcolor = $j('#flv'+flvclrcnt).attr('color');
                        firstprocolor = flvcolor;
                        
                        $j('<div>', { 
                            id: 'outsidediv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#outsidediv_size').addClass('new_outsidedivtriple');
                        $j('#outsidediv_size').css('background-color',flvcolor);


                        $j('<div>', { 
                            id: 'outsidediv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#outsidediv').addClass('new_outsidedivtriple');
                        $j('#outsidediv').css('background-color',flvcolor);


                        $j('<div>', { 
                            id: 'outsidediv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#outsidediv_nico').addClass('new_outsidedivtriple');
                        $j('#outsidediv_nico').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'outsidediv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#outsidediv_shot').addClass('new_outsidedivtriple');
                        $j('#outsidediv_shot').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'outsidediv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        $j('#outsidediv_finished_last').addClass('new_outsidedivdouble');
                        $j('#outsidediv_finished_last').css('background-color',flvcolor);


                        $j('#choose_size_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_flavor_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_nicotin_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_shot_name_fl1').html($j('#flv'+flvclrcnt).attr('title'));

                        $j('.finished_flv1').show();
                        $j('.finished_flv1').html($j('#flv'+flvclrcnt).attr('title')+' (33%)');  

                        $j('#choose_size_fl1').show(); 
                        $j('#choose_flavor_fl1').show(); 
                        $j('#choose_nicotin_fl1').show();
                        $j('#choose_shot_fl1').show();

                        $j('#choose_size_percentage_fl1').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl1').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl1').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl1').html(' Equal Mix');
                        /*flv1_per = '33%';
                        flv2_per = '33%';
                        flv3_per = '33%';*/
                        flv1 = $j('#flv'+flvclrcnt).attr('title'); 
                        flv1_id = $j('#flv'+flvclrcnt).attr('product-id');                                         
                    }
                    else if(cnt==1) {

                        secondcolorid = flvclrcnt;

                        flvcolor = $j('#flv'+flvclrcnt).attr('color');

                        if(firstprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colortwo');
                        }

                        secondprocolor = flvcolor;

                        $j('<div>', { 
                            id: 'innerdiv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#innerdiv_size').addClass('new_innerdivtriple');
                        $j('#innerdiv_size').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'innerdiv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#innerdiv').addClass('new_innerdivtriple');
                        $j('#innerdiv').css('background-color',flvcolor);


                        $j('<div>', { 
                            id: 'innerdiv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#innerdiv_nico').addClass('new_innerdivtriple');
                        $j('#innerdiv_nico').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'innerdiv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#innerdiv_shot').addClass('new_innerdivtriple');
                        $j('#innerdiv_shot').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'innerdiv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        $j('#innerdiv_finished_last').addClass('innerdiv_shot');
                        $j('#innerdiv_finished_last').addClass('new_innerdivtriple');
                        $j('#innerdiv_finished_last').css('background-color',flvcolor);

                        $j('#choose_size_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_flavor_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_nicotin_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_shot_name_fl2').html($j('#flv'+flvclrcnt).attr('title'));

                        $j('.finished_flv2').show();
                        $j('.finished_flv2').html($j('#flv'+flvclrcnt).attr('title')+' (33%)');   

                        $j('#choose_size_fl2').show();
                        $j('#choose_flavor_fl2').show();
                        $j('#choose_nicotin_fl2').show();
                        $j('#choose_shot_fl2').show();

                        $j('#choose_size_percentage_fl2').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl2').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl2').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl2').html(' Equal Mix');

                        flv2 = $j('#flv'+flvclrcnt).attr('title');
                        flv2_id = $j('#flv'+flvclrcnt).attr('product-id');
                    }
                    else if(cnt==2) {

                        thirdcolorid = flvclrcnt;
                        
                        flvcolor = $j('#flv'+flvclrcnt).attr('color');

                        if(firstprocolor == flvcolor){
                            flvcolor = $j('#flv'+firstcolorid).attr('colorthree');
                        }
                        else if(secondprocolor == flvcolor){
                            flvcolor = $j('#flv'+secondcolorid).attr('colortwo');
                        }
                        
                        thirdprocolor = flvcolor;

                        $j('<div>', { 
                            id: 'subinnerdiv_size'
                        }).appendTo('#sub_block_2_size');
                        
                        $j('#subinnerdiv_size').addClass('new_subinnerdivtriple');
                        $j('#subinnerdiv_size').css('background-color',flvcolor); 


                        $j('<div>', { 
                            id: 'subinnerdiv'
                        }).appendTo('#sub_block_2');
                        
                        $j('#subinnerdiv').addClass('new_subinnerdivtriple');
                        $j('#subinnerdiv').css('background-color',flvcolor); 


                        $j('<div>', { 
                            id: 'subinnerdiv_nico'
                        }).appendTo('#sub_block_2_nico');
                        $j('#subinnerdiv_nico').addClass('new_subinnerdivtriple');
                        $j('#subinnerdiv_nico').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'subinnerdiv_shot'
                        }).appendTo('#sub_block_2_shot');
                        
                        $j('#subinnerdiv_shot').addClass('new_subinnerdivtriple');
                        $j('#subinnerdiv_shot').css('background-color',flvcolor);

                        $j('<div>', { 
                            id: 'subinnerdiv_finished_last'
                        }).appendTo('#sub_block_2_finished_last');
                        $j('#subinnerdiv_finished_last').addClass('subinnerdiv_shot');
                        $j('#subinnerdiv_finished_last').addClass('new_subinnerdivtriple');
                        $j('#subinnerdiv_finished_last').css('background-color',flvcolor);

                        $j('#choose_size_name_fl3').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_flavor_name_fl3').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_nicotin_name_fl3').html($j('#flv'+flvclrcnt).attr('title'));
                        $j('#choose_shot_name_fl3').html($j('#flv'+flvclrcnt).attr('title'));


                        $j('.finished_flv3').show();
                        $j('.finished_flv3').html($j('#flv'+flvclrcnt).attr('title')+' (33%)');    

                        flv3 = $j('#flv'+flvclrcnt).attr('title');
                        flv3_id = $j('#flv'+flvclrcnt).attr('product-id');

                        $j('#choose_size_fl3').show();
                        $j('#choose_flavor_fl3').show();
                        $j('#choose_nicotin_fl3').show();
                        $j('#choose_shot_fl3').show();

                        $j('#choose_size_percentage_fl3').html(' Equal Mix');
                        $j('#choose_flavor_percentage_fl3').html(' Equal Mix');
                        $j('#choose_nicotin_percentage_fl3').html(' Equal Mix');
                        $j('#choose_shot_percentage_fl3').html(' Equal Mix');
                    }
                    cnt++;
                }
            });
        }
        else {
            var imgcorrectcnt = 1;
            $j('.flv').each(function() { //loop through each checkbox
                $j("#"+imgcorrectcnt+'flvforeimage').hide();
                this.checked = false;
                imgcorrectcnt++;
            });
            flv1 = '';
            flv2 = '';
            flv3 = '';
            flv1_id = '';
            flv2_id = '';
            flv3_id = '';
            $j('#outsidediv').remove();
            $j('#innerdiv').remove();
            $j('#subinnerdiv').remove();
            $j('#choose_flavor_fl1').hide();
            $j('#choose_flavor_fl2').hide();
            $j('#choose_flavor_fl3').hide();
            $j('#mgsize').html('00');
            $j('#nicotinesize').html('0');
            $j('#draggable').hide();
            $j('#draggable2').hide();
        }
    }
    else {
        /*var imgcorrectcnt = 1;
        $j('.flv').each(function() { //loop through each checkbox
            $j("#"+imgcorrectcnt+'flvforeimage').hide();
            this.checked = false;
            imgcorrectcnt++;
        });
        flv1 = '';
        flv2 = '';
        flv3 = '';
        $j('#outsidediv').remove();
        $j('#innerdiv').remove();
        $j('#subinnerdiv').remove();
        $j('#choose_flavor_fl1').hide();
        $j('#choose_flavor_fl2').hide();
        $j('#choose_flavor_fl3').hide();
        $j('#mgsize').html('00');
        $j('#nicotinesize').html('0');
        $j('#draggable').hide();
        $j('#draggable2').hide();*/
        $j('#'+id+'flvforeimage').hide();
        $j('#flv'+id).checked = false;
        alert('Please select up to three flavours');  
        return false;
    }
}

function set_default_per(){
    var default_height = '330';
    var total_flv = 0;
    var outsidediv_px = '';
    var innerdiv_px = '';
    var subinnerdiv_px = '';

    if(default_flv_per != '' && default_flv_per != '0'){
        total_flv++;
    }
    if(default_flv1_per != '' && default_flv1_per != '0'){
        total_flv++;   
    }
    if(default_flv2_per != '' && default_flv2_per != '0'){
        total_flv++;
    }

    if(total_flv == 2){

        var first_per = default_flv_per;
        var sec_per = default_flv1_per;

        if(default_flv_per == '' || default_flv_per == '0'){
            first_per = default_flv1_per;
            sec_per = default_flv2_per;
        }
        if(default_flv1_per == '' || default_flv1_per == '0'){
            sec_per = default_flv3_per;
        }

        outsidediv_px = (default_height * first_per)/(100);
        innerdiv_px = default_height - outsidediv_px;

        $j('#draggable').css('top',outsidediv_px+'px');

        $j('#outsidediv').removeClass('new_outsidedivdouble');
        $j('#innerdiv').removeClass('new_innerdivdouble');
        $j('#outsidediv_size').removeClass('new_outsidedivdouble');
        $j('#innerdiv_size').removeClass('new_innerdivdouble');
        $j('#outsidediv_nico').removeClass('new_outsidedivdouble');
        $j('#innerdiv_nico').removeClass('new_innerdivdouble');
        $j('#outsidediv_shot').removeClass('new_outsidedivdouble');
        $j('#innerdiv_shot').removeClass('new_innerdivdouble');
        $j('#outsidediv_finished_last').removeClass('new_outsidedivdouble');
        $j('#innerdiv_finished_last').removeClass('new_innerdivdouble');

        $j('#outsidediv').css('height',outsidediv_px+'px');
        $j('#innerdiv').css('height',innerdiv_px+'px');
        $j('#outsidediv_size').css('height',outsidediv_px+'px');
        $j('#innerdiv_size').css('height',innerdiv_px+'px');
        $j('#outsidediv_nico').css('height',outsidediv_px+'px');
        $j('#innerdiv_nico').css('height',innerdiv_px+'px');
        $j('#outsidediv_shot').css('height',outsidediv_px+'px');
        $j('#innerdiv_shot').css('height',innerdiv_px+'px');
        $j('#outsidediv_finished_last').css('height',outsidediv_px+'px');
        $j('#innerdiv_finished_last').css('height',innerdiv_px+'px');


        $j('#choose_flavor_percentage_fl1').html(first_per+'%');
        $j('#choose_flavor_percentage_fl2').html(sec_per+'%');
        $j('#choose_size_percentage_fl1').html(first_per+'%');
        $j('#choose_size_percentage_fl2').html(sec_per+'%');
        $j('#choose_nicotin_percentage_fl1').html(first_per+'%');
        $j('#choose_nicotin_percentage_fl2').html(sec_per+'%');
        $j('#choose_shot_percentage_fl1').html(first_per+'%');
        $j('#choose_shot_percentage_fl2').html(sec_per+'%');
    }
    if(total_flv == 3){

        outsidediv_px = (default_height * default_flv_per)/(100);
        innerdiv_px = (default_height * default_flv1_per)/(100);
        subinnerdiv_px = default_height - (outsidediv_px + innerdiv_px);
        
        var drag2_pos = (outsidediv_px + innerdiv_px) - 30;
        
        $j('#draggable').css('top',outsidediv_px+'px');
        $j('#draggable2').css('top',drag2_pos+'px');

        $j('#outsidediv').removeClass('new_outsidedivtriple');
        $j('#innerdiv').removeClass('new_innerdivtriple');
        $j('#subinnerdiv').removeClass('new_subinnerdivtriple');
        $j('#outsidediv_size').removeClass('new_outsidedivtriple');
        $j('#innerdiv_size').removeClass('new_innerdivtriple');
        $j('#subinnerdiv_size').removeClass('new_subinnerdivtriple');
        $j('#outsidediv_nico').removeClass('new_outsidedivtriple');
        $j('#innerdiv_nico').removeClass('new_innerdivtriple');
        $j('#subinnerdiv_nico').removeClass('new_subinnerdivtriple');
        $j('#outsidediv_shot').removeClass('new_outsidedivtriple');
        $j('#innerdiv_shot').removeClass('new_innerdivtriple');
        $j('#subinnerdiv_shot').removeClass('new_subinnerdivtriple');
        $j('#outsidediv_finished_last').removeClass('new_outsidedivtriple');
        $j('#innerdiv_finished_last').removeClass('new_innerdivtriple');
        $j('#subinnerdiv_finished_last').removeClass('new_subinnerdivtriple');
        

        $j('#outsidediv').css('height',outsidediv_px+'px');
        $j('#innerdiv').css('height',innerdiv_px+'px');
        $j('#subinnerdiv').css('height',subinnerdiv_px+'px');
        $j('#outsidediv_size').css('height',outsidediv_px+'px');
        $j('#innerdiv_size').css('height',innerdiv_px+'px');
        $j('#subinnerdiv_size').css('height',subinnerdiv_px+'px');
        $j('#outsidediv_nico').css('height',outsidediv_px+'px');
        $j('#innerdiv_nico').css('height',innerdiv_px+'px');
        $j('#subinnerdiv_nico').css('height',subinnerdiv_px+'px');
        $j('#outsidediv_shot').css('height',outsidediv_px+'px');
        $j('#innerdiv_shot').css('height',innerdiv_px+'px');
        $j('#subinnerdiv_shot').css('height',subinnerdiv_px+'px');
        $j('#outsidediv_finished_last').css('height',outsidediv_px+'px');
        $j('#innerdiv_finished_last').css('height',innerdiv_px+'px');
        $j('#subinnerdiv_finished_last').css('height',subinnerdiv_px+'px');


        $j('#choose_flavor_percentage_fl1').html(default_flv_per+'%');
        $j('#choose_flavor_percentage_fl2').html(default_flv1_per+'%');
        $j('#choose_flavor_percentage_fl3').html(default_flv2_per+'%');
        $j('#choose_size_percentage_fl1').html(default_flv_per+'%');
        $j('#choose_size_percentage_fl2').html(default_flv1_per+'%');
        $j('#choose_size_percentage_fl3').html(default_flv2_per+'%');
        $j('#choose_nicotin_percentage_fl1').html(default_flv_per+'%');
        $j('#choose_nicotin_percentage_fl2').html(default_flv1_per+'%');
        $j('#choose_nicotin_percentage_fl3').html(default_flv2_per+'%');
        $j('#choose_shot_percentage_fl1').html(default_flv_per+'%');
        $j('#choose_shot_percentage_fl2').html(default_flv1_per+'%');
        $j('#choose_shot_percentage_fl3').html(default_flv2_per+'%');
        
    }
}

function share_ontwitter(){

    var eData1 = {
        product_id1 : flv1_id,
        product_id2 : flv2_id,
        product_id3 : flv3_id,
        product1_per : $j('#flaver1_per').val(),
        product2_per : $j('#flaver2_per').val(),
        product3_per : $j('#flaver3_per').val(),
        blender_name : brand_name,
        size : size_value,
        nicotine : nocotine_value,
        extra_shot_id : ext_shot_id,
    };
        
    $j.ajax({
        url:base_url+'eliquidblender/index/blenderdata',
        type:'POST',
        data:{data:eData1},
        success:function(iBlenderId){
            if(iBlenderId != ''){
                inviteurl = base_url+'eliquidblender/index/?blender_id='+iBlenderId;
                var newinviteurl = inviteurl.split('/').join('%2F');
                var finalinviteurl = newinviteurl.split('::').join('%3F');
                var newtwitterurl = 'http://twitter.com/home?status= Product Title : '+brand_name+' URL : '+finalinviteurl;
                window.location = newtwitterurl;
            }
        }
    });
}