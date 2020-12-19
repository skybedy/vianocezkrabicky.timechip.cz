$(function(){

    var forms = document.querySelectorAll('.needs-validation')
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
      .forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            else{
                event.preventDefault()
                event.stopPropagation()
                const data =  new FormData($('#formCustomer')[0]);
                $.ajax({
                    method: 'post',
                    processData: false,
                    contentType: false,
                    cache: false,
                    data: data,
                    enctype: 'multipart/form-data',
                    url: location.href+'api/insert-to-db',
                    //dataType: "json",
                    success : function(data){
                        console.log(data)
                    }
                });
            }
            form.classList.add('was-validated')
        }, false)
      })
      
      $('select#pohlavi').change(function(){
        var pohlavi = $(this).add("option:selected").val();
        $.getJSON(location.href+'api/vypis-roku/'+pohlavi,function(xhr){
            var str = '<div class="col-12"><label for="rocnik" class="form-label">Rok narodenia</label><select class="form-select" id="rocnik" name="rocnik" required><option value="" selected disabled></option>';
            for(var i=0;i < xhr.length;i++){
                str += '<option value="'+xhr[i]+'">'+xhr[i]+'</option>';
            }
            str += '</select><div class="invalid-feedback">Je potřeba uvést rok narození</div></div>';
            $("#roky").html(str);
        });
        return false;
    });
});