/*jslint browser: true*/
/*global $, jQuery*/

(function(){
    "use strict";

    $(".datepicker").datepicker({
        format: 'yyyy-mm-dd',

    });

<<<<<<< HEAD
    $(".span12").on('change', ".updateCIMBL", function(){
        $.ajax({
            url: $(this).parent().attr('action'),
            type: 'POST',
            dataType: 'json',
            data: $(this).parent().serialize(),
            success: function(data){
                if(data.msg !== null){
                    alert(data.msg);
                }
            }
        });
    });

    $("#container").on('change', '.checkbox', function(){
        var Attribute = {
            id: $(this).val(),
            to_ids: $(this).is(":checked") || false
        };
        $.ajax({
            url: '/attributes/toggle',
            type: 'POST',
            data: {
                Attribute: Attribute
            },
            dataType: 'json',
            success: function(data){
                console.log(data);
            }
        });
    });

=======
>>>>>>> develop
}());