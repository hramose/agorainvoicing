<div class="modal fade" id="domain">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Domain</h4>
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                
            </div>
            <div class="modal-body">
                <!-- Form  -->
                {!! Form::open(['url'=>'checkout','method'=>'get','id'=>'domain1']) !!}
                
                @foreach($domain as $product)
                
                <div class="form-group {{ $errors->has('domain') ? 'has-error' : '' }}">
                    <!-- name -->
                    {!! Form::label('domain',Lang::get('message.domain'),['class'=>'required']) !!}
                    {!! Form::text('domain['.$product.']',null,['class' => 'form-control' ,'id'=>'validDomain']) !!}
                           <h6 id ="domaincheck"></h6>
                </div>
                
                
                @endforeach
       
              
            </div>
            <div class="modal-footer">
                <button type="button" id="close" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary" value="{{Lang::get('message.save')}}">
            </div>
             {!! Form::close()  !!}
        

            <!-- /Form -->
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->  
@section('script')
  <script type="text/javascript">

 $(document).ready(function(){
 $('#domain1').submit(function(){
      $('#domaincheck').hide();
   
   var domErr = true;
    function validdomaincheck(){

            var pattern = new RegExp(/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/);
              if (pattern.test($('#validDomain').val())){
                 $('#domaincheck').hide();
                 $('#validDomain').css("border-color","");
                 return true;
               
              }
              else{
                 $('#domaincheck').show();
                $('#domaincheck').html("Domain name field is required in the format 'example.com'");
                 $('#domaincheck').focus();
                  $('#validDomain').css("border-color","red");
                 $('#domaincheck').css({"color":"red","margin-top":"5px"});
                   domErr = false;
                    return false;
              
    }

   }
    validdomaincheck();
    if(validdomaincheck()){
        return true;
     }
     else{
        return false;
     }
       });
});
   


</script>
@stop