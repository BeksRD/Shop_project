function fill(Value) {
    $('#search').val(Value);
    $('#products').hide();
}
$(document).ready(function () {
    $("#search").keyup(function() {
        let thisPage = $(".products").data("id");
        window.name = $('#search').val();
        if (name == ''){
            location.reload();
        }else {
            $.ajax({
                type:"POST",
                url:"/result/show/1",
                data:{
                    search:name
                },
                success:function (html){
                    $("#products").html(html).show();
                }
            });
        }
    });
    $("html").on('click',".paginate", function (event) {
        let $id = $(this).data('id');
        $.ajax({
            type: "GET",
            url: "/result/show/"+$id,
            data: {
              search:name
            },
            success:function (html) {
                $("#products").html(html).show();
            }
        });
        event.preventDefault();
        return false;
    });
});





