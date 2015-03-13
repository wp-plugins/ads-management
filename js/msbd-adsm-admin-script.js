(function($) {    
    
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
                $(".size-wh").prop("readonly", false);
            } else {
                $(".size-wh").val("").prop("readonly", true);
                
            }            
        }); 
        
        $("#adsmp_adv_sizes").trigger("change");
    }());

    
}(jQuery));
