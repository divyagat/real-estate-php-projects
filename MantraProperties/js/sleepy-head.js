// var inactiveTime;
// $('*').bind('mousemove click mouseup mousedown keydown keypress keyup submit change mouseenter scroll resize dblclick', function () {

//     function alertUser() {
//         $('#exampleModal').modal('show');
//         //alert("User is inactive.");
//     }

//     clearTimeout(inactiveTime);

//     inactiveTime = setTimeout(alertUser, 1000 * 15); // 5 seconds
// });
// $("body").trigger("mousemove");

if (window.location.href.indexOf("blog") > -1) {
//       alert("Blog");
//     }

if ($.cookie('bas_referral') === null) {
    // set cookie  
    var cookURL = $.cookie('bas_referral', '', { path: '/' });
}
else 
{
    var cookieValue = $.cookie("bas_referral");

    if (cookieValue !== '') {
    }
    else {
        var inactiveTime;
        $('*').bind('mousemove click mouseup mousedown keydown keypress keyup submit change mouseenter scroll resize dblclick', function () {

            function alertUser() {
                $('#exampleModal').modal('show');
            }
            clearTimeout(inactiveTime);
            inactiveTime = setTimeout(alertUser, 1000 * 86400); // 1Day
        });
        $("body").trigger("mousemove");


    }
}
}
//////////////////////////////////////////////////////////////
$(".btn-close").on('click', function () {
    $.cookie('bas_referral', "popup", { path: '/' });
    location.reload(true);
});
$("#mdl_submit").on('click', function () {
    $.cookie('bas_referral', "popup", { path: '/' });
    // location.reload(true);
});