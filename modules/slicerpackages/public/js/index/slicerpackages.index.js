$(document).ready(function() {

  var webroot = $('.webroot').val();
  $('.packageLinks li a').prepend('<img alt="" src="' + webroot + '/core/public/images/icons/download.png" />');
});
