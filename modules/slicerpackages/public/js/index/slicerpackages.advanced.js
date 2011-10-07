var currentOs = '';
var currentArch = '';
var currentBuild = '';

function fillDataTable(os, arch, build)
  {
  if(typeof(os) == 'undefined')
    {
    os = $('span.choice [type="radio"][name="osGroup"]:checked').val();
    }
  if(typeof(arch) == 'undefined')
    {
    arch = $('span.choice [type="radio"][name="archGroup"]:checked').val();
    }
  if(typeof(build) == 'undefined')
    {
    build = $('span.choice [type="radio"][name="buildGroup"]:checked').val();
    }
  if(currentOs != os || currentArch != arch || currentBuild != build)
    {
    //alert('clicked os:' + os + ', arch:' + arch + ', build:' + build);
    $('#dataTableContent').html("");
    $("#dataTableLoading").show();
    webroot = $('.webroot').val();
    parameters = '';
    if(os != 'any') { parameters+= '&os=' + os; }
    if(arch != 'any') { parameters+= '&arch=' + arch; }
    if(build != 'any') { parameters+= '&submissiontype=' + build; }
    ajaxWebApi.ajax({
      method: 'midas.slicerpackages.get.packages',
      args: parameters,
      complete: function() {
        $("#dataTableLoading").hide();
        },
      success: function(data) {
        //var templates = {
        //  td : '  <td>#{td}</td>',
        //  td_os : '  <td class="os #{cell}">#{cell}</td>',
        //  td_download : '<td class="link"><a href="' + webroot + '/download/?items=#{cell}">Download</a></td>'
        //};

        var tablecontent = '';
        $.each(json.data, function (key, val) {
          tablecontent += '<tr>';
          //tablecontent += $.tmpl(templates.td, {cell: val.item_id});
          //tablecontent += $.tmpl(templates.td_os, {cell: val.os});
          //tablecontent += $.tmpl(templates.td, {cell: val.arch});
          //tablecontent += $.tmpl(templates.td, {cell: val.submissiontype});
          //tablecontent += $.tmpl(templates.td, {cell: val.revision});
          //tablecontent += $.tmpl(templates.td_download, {cell: val.item_id});
          tablecontent += '  <td>' + val.item_id + '</td>';
          tablecontent += '  <td class="os ' + val.os + '">' + val.os + '</td>';
          tablecontent += '  <td>' + val.arch + '</td>';
          tablecontent += '  <td>' + val.submissiontype + '</td>';
          tablecontent += '  <td>' + val.revision + '</td>';
          tablecontent += '  <td class="link">'
          tablecontent += '    <a href="' + webroot + '/download/?items=' + val.item_id + '">Download</a>';
          tablecontent += '  </td>';
          tablecontent += '</tr>';
        });
        
        $('#dataTableContent').html(tablecontent);
        $("#dataTableLoading").hide();
        }
      });
    currentOs = os;
    currentArch = arch;
    currentBuild = build;
    }
  }

$(document).ready(function() {
  $('#dataTable').tablesorter();
  
  var client_os_map = { 'Windows' : 'win', 'Mac' : 'macosx', 'Linux' : 'linux' };
  $('span.choice [type="radio"][name="osGroup"][value="' + client_os_map[$.client.os] + '"]').prop("checked", "checked");
  
  var client_arch_map = { '32-bit' : 'i386', '64-bit' : 'amd64' };
  $('span.choice [type="radio"][name="archGroup"][value="' + client_arch_map[$.client.arch] + '"]').prop("checked", "checked");
  
  $('span.choice [type="radio"]').click(function(){fillDataTable(); });
  fillDataTable();
  });