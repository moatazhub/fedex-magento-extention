require([
    "jquery",
    "mage/url",
    'Magento_Ui/js/modal/modal'
],function($,url){
    $('#awp_print').on('click', function(){
        var shipId = $(this).attr("data-shipment_id");
        jQuery('body').trigger('processStart');
        jQuery.ajax({
            url: url.build('/egyptexpress/index/awpprint/shipment_id/'+shipId),
            type: "GET",
            data: jQuery('#awp_print_form').serialize(),
            success: function(response){
                jQuery('body').trigger('processStop');

                jQuery( "#awpresponse" ).remove();
                jQuery("#container").after("<div id='awpresponse' style='display:none;'><div id='addresponsedata'></div></div>");
                jQuery("#addresponsedata").empty('');
                jQuery('#addresponsedata').html(response.data);

                try {
                    var responsecodes = jQuery.parseJSON(response.data);
                    var coder = responsecodes['response_code'];
                    if(coder == '200'){
                        alert(coder);
                        setTimeout(function() {
                            var printContents = document.getElementById('awpresponse').innerHTML;
                            var originalContents = document.body.innerHTML;
                            document.body.innerHTML = printContents;
                            window.print();
                            document.body.innerHTML = originalContents;
                            location.reload();  }, 2000);
                    }else{
                        var msgr = responsecodes['response_message'];
                        alert(msgr);
                    }
                } catch (e) {
                    setTimeout(function() {
                        var printContents = document.getElementById('awpresponse').innerHTML;
                        var originalContents = document.body.innerHTML;
                        document.body.innerHTML = printContents
                        window.print();
                        document.body.innerHTML = originalContents;
                        location.reload();  }, 2000);
                }
            }
        });
    })

});