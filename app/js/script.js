/**
 * Created by eygle on 9/11/15.
 */

$(document).ready(function() {
    var showMenu = false;
    $("#left-menu-icon").click(function() {
        showMenu = !showMenu;
        if (showMenu) {
            $("#left-menu").show().scrollTop(0);
        }
        else
            $("#left-menu").hide();
    });
});
