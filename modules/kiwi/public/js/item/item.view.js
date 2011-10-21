$(document).ready(function() {
  $('#viewInKiwiLink').attr('href','kiwiviewer://'+document.location.href.split('item')[0].split('http://')[1]+$('#viewInKiwiLink').attr('href'));

  });
