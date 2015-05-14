(function($) {    

    jQuery.fn.check_exists = function(){ return this.length > 0; }
    
    
    $(".msbd-adsmp").on("click", ".handlediv", function(e){       
        e.preventDefault();        
        $(this).parent().toggleClass("closed");
    });


    // action for #adsmp_adv_sizes on msbd_adsmp_add_edit page
    (function adsmp_adv_sizes_change() {
        $("#adsmp_adv_sizes").on("change",function(e){
            var thisVal = $( "#adsmp_adv_sizes option:selected" ).val();
            console.log("on change ..."+thisVal);
            
            if(thisVal=="") {                
                var edit_val = parseInt($( "input[name=msbd_adsmp_edit]" ).val());
                if(edit_val==0) {                
                    $(".size-wh").prop("readonly", false);
                }
            } else {
                $(".size-wh").val("").prop("readonly", true);
                
            }            
        }); 
        
        $("#adsmp_adv_sizes").trigger("change");
    }());
    
    

    
    if( $('.adsmp-masonry-wrapper').check_exists() ) { //Add Masonry script only if "masonry-wrapper" exist
        var $masonry_wrapper = $('.adsmp-masonry-wrapper');
        // initialize
        $masonry_wrapper.masonry({
            itemSelector: '.cat_box'
        });
    }
    
}(jQuery));



