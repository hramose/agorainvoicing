@extends('themes.default1.layouts.master')
@section('content')
<div class="box box-primary">

    <div class="box-header">
        @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(Session::has('success'))
        <div class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('success')}}
        </div>
        @endif
        <!-- fail message -->
        @if(Session::has('fails'))
        <div class="alert alert-danger alert-dismissable">
            <i class="fa fa-ban"></i>
            <b>{{Lang::get('message.alert')}}!</b> {{Lang::get('message.failed')}}.
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('fails')}}
        </div>
        @endif
        <div id="response"></div>

        <h4>{{Lang::get('message.pages')}}
            <a href="{{url('pages/create')}}" class="btn btn-primary pull-right   ">{{Lang::get('message.create')}}</a></h4>
    </div>


    <div class="box-body">
        <div class="row">

            <div class="col-md-12">
        
<table id="pages-table" class="table display" cellspacing="0" width="100%" styleClass="borderless">
                      <button  value="" class="btn btn-danger btn-sm btn-alldell" id="bulk_delete">Delete Selected</button><br /><br />
                    <thead><tr>
                         <th class="no-sort"><input type="checkbox" name="select_all" onchange="checking(this)"></th>
                         <th>Name</th>
                          <th>Url</th>
                           <th>Created At</th>
                            <th>Content</th>
                            <th>Action</th>
                        </tr></thead>
                     </table>
            </div>
        </div>

    </div>

</div>



<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" />
<script src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
        $('#pages-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route('get-pages') !!}',
            "oLanguage": {
                "sLengthMenu": "_MENU_ Records per page",
                "sSearch"    : "Search: ",
                "sProcessing": '<img id="blur-bg" class="backgroundfadein" style="top:40%;left:50%; width: 50px; height:50 px; display: block; position:    fixed;" src="{!! asset("lb-faveo/media/images/gifloader3.gif") !!}">'
            },
                columnDefs: [
                { 
                    targets: 'no-sort', 
                    orderable: false,
                    order: []
                }
            ],
          
            columns: [
                {data: 'checkbox', name: 'checkbox'},
                {data: 'name', name: 'name'},
                {data: 'url', name: 'Url'},
                {data: 'created_at', name: 'Created At'},
                {data: 'content', name: 'Content'},
                
                {data: 'action', name: 'Action'}
            ],
            "fnDrawCallback": function( oSettings ) {
                $('.loader').css('display', 'none');
            },
            "fnPreDrawCallback": function(oSettings, json) {
                $('.loader').css('display', 'block');
            },
        });
    </script>

@stop

@section('icheck')
<script>
    function checking(e){
          
          $('#pages-table').find("td input[type='checkbox']").prop('checked', $(e).prop('checked'));
     }
     

     $(document).on('click','#bulk_delete',function(){
      var id=[];
      if (confirm("Are you sure you want to delete this?"))
        {
            $('.page_checkbox:checked').each(function(){
              id.push($(this).val())
            });
            if(id.length >0)
            {
               $.ajax({
                      url:"{!! route('pages-delete') !!}",
                      method:"get",
                      data: $('#check:checked').serialize(),
                      beforeSend: function () {
                $('#gif').show();
                },
                success: function (data) {
                $('#gif').hide();
                $('#response').html(data);
                location.reload();
                }
               })
            }
            else
            {
                alert("Please select at least one checkbox");
            }
        }  

     });
</script>
@stop