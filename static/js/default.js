$(function(){
    $('select#pohlavi').change(function(){
        var pohlavi = $(this).add("option:selected").val();
        $.getJSON('http://localhost:1302/api/vypis-roku/'+pohlavi,function(xhr){
            var str = '<div class="col-12"><label for="" class="form-label">Rok narodenia</label><select class="form-select" id="country" required><option value="" selected disabled></option>';
                for(var i=0;i < xhr.length;i++){
                    str += '<option value="'+xhr[i]+'">'+xhr[i]+'</option>';
                }
            str += '</select></div>';
            $("#roky").html(str);
        });
        return false;
    });
});