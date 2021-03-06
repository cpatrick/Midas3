var midas = midas || {};
midas.browser = midas.browser || {};

midas.ajaxSelectRequest= '';

/**
 * Callback when a row is selected
 * Pass null if there is no selected row.
 */
midas.genericCallbackSelect = function(node) {
    if(!node) {
        $('div.ajaxInfoElement').html('');
        $('div.viewAction ul').html('');
        return;
    }
    $('img.infoLoading').show();
    $('div.ajaxInfoElement').html('');
    if(midas.ajaxSelectRequest != '') {
        midas.ajaxSelectRequest.abort();
    }
    midas.createAction(node);
    midas.ajaxSelectRequest = $.ajax(
        {
            type: "POST",
            url: json.global.webroot+'/browse/getelementinfo',
            data: {type: node.attr('type'), id: node.attr('element')},
            success: function(jsonContent) {
                midas.createInfo(jsonContent);
                $('img.infoLoading').hide();
            }
        });
};

midas.genericCallbackCheckboxes = function(node) {
    var arraySelected = [];
    arraySelected['folders'] = [];
    arraySelected['items'] = [];
    var folders = '';
    var items = '';
    node.find(".treeCheckbox:checked").each(
        function() {
            if($(this).parents('tr').attr('type')!='item') {
                arraySelected['folders'].push($(this).attr('element'));
                folders += $(this).attr('element') + '-';
            }
            else {
                arraySelected['items'].push($(this).attr('element'));
                items += $(this).attr('element') + '-';
            }
        });

    if((arraySelected['folders'].length + arraySelected['items'].length) > 0) {
        $('div.viewSelected').show();
        var html = ' (' + (arraySelected['folders'].length + arraySelected['items'].length);
        html += ' ' + json.browse.element;
        if((arraySelected['folders'].length + arraySelected['items'].length) > 1) {
            html += 's';
            $('div.sideElementActions').hide();
        }
        html += ')';
        $('div.viewSelected h1 span').html(html);
        var links = '<ul>';
        links += '<li style="background-color: white;">';
        links += '  <img alt="" src="' + json.global.coreWebroot + '/public/images/icons/download.png"/> ';
        links += '  <a href="' + json.global.webroot + '/download?folders=' + folders + '&items=' + items + '">' + json.browse.download + '</a></li>';
        links += '</li>';

        if(json.global.logged) {
            links += '<li style="background-color: white;">';
            links += '  <img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> ';
            links += '  <a onclick="midas.deleteSelected(\''+ folders + '\',\'' + items + '\')">' + json.browse.deleteSelected + '</a></li>';
            links += '</li>';
        }
        links += '</ul>';
        $('div.viewSelected>span').html(links);
        $('div.viewSelected li a').hover(
            function(){
                $(this).parents('li').css('background-color','#E5E5E5');
            }, function() {
                $(this).parents('li').css('background-color','white');
            });
    }
    else {
        $('div.viewSelected').hide();
        $('div.viewSelected span').html('');
    }
};

midas.genericCallbackDblClick = function(node) {
  // no-op currently
};

midas.createNewFolder = function (id) {
    loadDialog('folderId'+id,'/folder/createfolder?folderId='+id);
    showDialog(json.browse.createFolder,false);
    $('#createFolderForm input[name=name]').val('');
    $('#createFolderForm textarea[name=description]').val('');
    $('#createFolderForm input[name=teaser]').val('');
};

midas.removeNodeFromTree = function (node, recursive) {
    if(!node || node.length == 0) {
        return;
    }
    var ancestorNodes = midas.ancestorsOf(node);
    if(recursive) {
        midas.removeChildren(node);
    }
    node.remove();
    // mark ancestor nodes
    for (var curNode in ancestorNodes) {
        var jCurNode = $(ancestorNodes[curNode]);
        jCurNode.find('span.elementCount').remove();
        jCurNode.find('span.elementSize').after("<img class='folderLoading'  element='"+jCurNode.attr('element')+"' alt='' src='"+json.global.coreWebroot+"/public/images/icons/loading.gif'/>");
        jCurNode.find('span.elementSize').remove();
    }
    // update folder size
    getElementsSize($('#browseTable')); //todo select table ancestor of node instead of using global scope
};

midas.removeItem = function (id) {
    var html='';
    html += json.browse['removeMessage'];
    html+='<br/>';
    html+='<br/>';
    html+='<br/>';
    html+='<div style="float: right;">';
    html+='<input class="globalButton deleteFolderYes" element="'+id+'" type="button" value="'+json.global.Yes+'"/>';
    html+='<input style="margin-left:15px;" class="globalButton deleteFolderNo" type="button" value="'+json.global.No+'"/>';
    html+='</div>';
    
    showDialogWithContent(json.browse['delete'],html,false);

    $('input.deleteFolderYes').unbind('click').click(
        function() {
            var node = $('table.treeTable tr[element='+id+']');
            var folder = midas.parentOf(node);
            var folderId = '';
            // we are in a subfolder view and the parent is the current folder
            if(folder) {
                folderId = folder.attr('element');
            }
            else {
                folderId = json.folder.folder_id;
            }

            $.post(json.global.webroot+'/folder/removeitem',
                   {folderId: folderId, itemId: id},
                   function(data) {
                       jsonResponse = jQuery.parseJSON(data);
                       if(jsonResponse==null) {
                           createNotice('Error',4000);
                           return;
                       }
                       if(jsonResponse[0]) {
                           createNotice(jsonResponse[1],1500);
                           $( "div.MainDialog" ).dialog('close');
                           midas.removeNodeFromTree(node, false);
                           midas.genericCallbackCheckboxes($('#browseTable'));
                           midas.genericCallbackSelect(null);
                       }
                       else {
                           createNotice(jsonResponse[1],4000);
                       }
                   });
        });
    $('input.deleteFolderNo').unbind('click').click(
        function() {
            $( "div.MainDialog" ).dialog('close');
        });
};

midas.deleteFolder = function (id) {
    var html = '';
    html += json.browse['deleteMessage'];
    html += '<br/><br/>';
    html += '<div style="float: right;">';
    html += '<input class="globalButton deleteFolderYes" element="'+id+'" type="button" value="' + json.global.Yes + '"/>';
    html += '<input style="margin-left:15px;" class="globalButton deleteFolderNo" type="button" value="' + json.global.No + '"/>';
    html += '</div>';
    html += '<img id="deleteFolderProgress" style="display: none;" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>';

    showDialogWithContent(json.browse['delete'], html, false);

    $('input.deleteFolderYes').unbind('click').click(function() {
        $('#deleteFolderProgress').show();
        $(this).attr('disabled', 'disabled');
        var node = $('table.treeTable tr.parent[element='+id+']');
        $.post(json.global.webroot+'/folder/delete', {folderId: id}, function(data) {
            $('#deleteFolderProgress').hide();
            $(this).removeAttr('disabled');
            jsonResponse = jQuery.parseJSON(data);
            if(jsonResponse==null) {
                createNotice('Error', 4000, 'error');
                return;
            }
            if(jsonResponse[0]) {
                createNotice(jsonResponse[1], 1500);
                $('div.MainDialog').dialog('close');
                midas.removeNodeFromTree(node, true);
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
            else {
                createNotice(jsonResponse[1],4000, 'error');
            }
        });
    });
    $('input.deleteFolderNo').unbind('click').click(function() {
        $('div.MainDialog').dialog('close');
    });
};

/**
 * Deletes the set of folders and items selected with the checkboxes.
 * The folders and items params should be strings of ids separated by - (empty
 * ids will be ignored)
 */
midas.deleteSelected = function (folders, items) {
    var html='';
    html+=json.browse['deleteSelectedMessage'];
    html+='<br/><br/><br/>';
    html+='<form class="genericForm"><div class="dialogButtons">';
    html+='  <input class="globalButton deleteSelectedYes" type="button" value="' + json.global.Yes + '"/>';
    html+='  <input class="globalButton deleteSelectedNo" type="button" value="' + json.global.No + '"/>';
    html+='</div></form>';
    html+='<img id="deleteSelectedLoadingGif" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>';

    showDialogWithContent(json.browse['deleteSelected'],html,false);
    $('input.deleteSelectedYes').unbind('click').click(function() {
        $('input.deleteSelectedYes').attr('disabled', 'disabled');
        $('input.deleteSelectedNo').attr('disabled', 'disabled');
        $('#deleteSelectedLoadingGif').show();
        $.post(json.global.webroot+'/browse/delete', {folders: folders, items: items}, function(data) {
            $('input.deleteSelectedYes').removeAttr('disabled');
            $('input.deleteSelectedNo').removeAttr('disabled');
            $('#deleteSelectedLoadingGif').hide();
            var resp = jQuery.parseJSON(data);
            if(resp == null) {
                createNotice('Error during folder delete. Check the log.', 4000);
                return;
            }
            if(resp.success) {
                var message = 'Deleted ' + resp.success.folders.length + ' folders and ';
                message += resp.success.items.length + ' items.';
                if(resp.failure.folders.length || resp.failure.items.length) {
                    message += ' Invalid delete permissions on ';
                    message += resp.failure.folders.length + ' folders and ';
                    message += resp.failure.items.length + ' items.';
                }
                createNotice(message, 5000);
                $('div.MainDialog').dialog('close');
                for (var curFolder in resp.success.folders) {
                    midas.removeNodeFromTree($('table.treeTable tr.parent[element='+resp.success.folders[curFolder]+']'), true);
                }
                for (var curItem in resp.success.items) {
                    midas.removeNodeFromTree($('table.treeTable tr[type=item][element='+resp.success.items[curItem]+']'), false);
                }
                midas.genericCallbackCheckboxes($('#browseTable'));
                midas.genericCallbackSelect(null);
            }
        });
    });
    $('input.deleteSelectedNo').unbind('click').click(function() {
        $('div.MainDialog').dialog('close');
    });
};

/**
 * Helper method to remove all of a node's subtree from the treeTable view.
 * Expects a jquerified node object.
 */
midas.removeChildren = function (node) {
    node.each(
        function() {
            var children = $("table.treeTable tbody tr.child-of-" + this.id);
            $(children).each(
                function(){
                    midas.removeChildren($(this));
                    $(this).remove();
                });
        });
};

midas.editFolder = function (id) {
    loadDialog("editFolder" + id,"/folder/edit?folderId=" + id);
    showDialog(json.browse.edit, false);
};

midas.moveFolder = function (id) {
  loadDialog("moveFolder"+id,"/browse/movecopy?move=true&folders="+id);
  showDialog(json.browse.move);
  }

midas.moveItem = function (itemId, fromFolderId) {
  loadDialog("moveItem"+itemId,"/browse/movecopy?move=true&items="+itemId+"&from="+fromFolderId);
  showDialog(json.browse.move);
  }

midas.parentOf = function (node) {
    var classNames = node[0].className.split(' ');

    for(key in classNames) {
        if(classNames[key].match("child-of-")) {
            return $("#" + classNames[key].substring(9));
        }
    }
};

midas.ancestorsOf = function (node) {
    var ancestors = [];
    while((node = midas.parentOf(node))) {
        ancestors[ancestors.length] = node[0];
    }
    return ancestors;
};

midas.createAction = function (node) {
    var type = node.attr('type');
    var element = node.attr('element');
    var policy = node.attr('policy');
    $('div.viewAction ul').fadeOut(
        'fast',
        function() {
            $('div.viewAction ul').html('');
            var html = '';
            if(type=='community') {
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/community/'+element+'">'+json.browse.view+'</a></li>';
            }
            if(type=='folder') {
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/folder/'+element+'">'+json.browse.view+'</a></li>';
                html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/download.png"/> <a href="'+json.global.webroot+'/download?folders='+element+'">'+json.browse.download+'</a></li>';
                if(policy>=1) {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/folder_add.png"/> <a onclick="midas.createNewFolder('+element+');">'+json.browse.createFolder+'</a></li>';
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/upload.png"/> <a rel="'+json.global.webroot+'/upload/simpleupload/?parent='+element+'" class="uploadInFolder">'+json.browse.uploadIn+'</a></li>';
                    if(node.attr('deletable')!=undefined && node.attr('deletable')=='true') {
                        html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/edit.png"/> <a onclick="midas.editFolder('+element+');">'+json.browse.edit+'</a></li>';
                        html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> <a onclick="midas.moveFolder('+element+');">'+json.browse.move+'</a></li>';
                    }
                }
                if(policy>=2) {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> <a type="folder" element="'+element+'" class="sharingLink">'+json.browse.share+'</a></li>';
                    if(node.attr('deletable')!=undefined && node.attr('deletable')=='true') {
                        html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a onclick="midas.deleteFolder('+element+');">'+json.browse['delete']+'</a></li>';
                    }
                }
            }
            if(type == 'item') {
                var from = midas.parentOf(node);
                if(from) {
                    var fromFolder = from.attr('element');
                }
                else { // we are in a subfolder view and the parent is the current folder
                    var fromFolder = json.folder.folder_id;
                }
                html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/item/'+element+'">'+json.browse.view+'</a></li>';
                html += '<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/download.png"/> <a href="'+json.global.webroot+'/download?items='+element+'">'+json.browse.downloadLatest+'</a></li>';
                if (policy>=2) {
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> <a  type="item" element="'+element+'" class="sharingLink">'+json.browse.share+'</a></li>';
                    html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/move.png"/> <a onclick="midas.moveItem(\''+ element + '\',\'' + fromFolder + '\');">'+json.browse.move+'</a></li>';
                    html+='<li class="removeItemLi"><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a onclick="midas.removeItem('+element+');">'+json.browse['removeItem']+'</a></li>';
                }
            }
            $('div.viewAction ul').html(html);
            $('div.viewAction li a').hover(
                function() {
                    $(this).parents('li').css('background-color','#E5E5E5');
                },
                function() {
                    $(this).parents('li').css('background-color','white');
                });

            $('a.uploadInFolder').qtip(
                {
                    content: {
                        // Set the text to an image HTML string with the correct src URL to the loading image you want to use
                        text: '<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />',
                        ajax: {
                            url: $('a.uploadInFolder').attr('rel') // Use the rel attribute of each element for the url to load
                        },
                        title: {
                            text: 'Upload', // Give the tooltip a title using each elements text
                            button: true
                        }
                    },
                    position: {
                        at: 'bottom center', // Position the tooltip above the link
                        my: 'top right',
                        viewport: $(window), // Keep the tooltip on-screen at all times
                        effect: true // Disable positioning animation
                    },
                    show: {
                        modal: {
                            on: true,
                            blur: false
                        },
                        event: 'click',
                        solo: true // Only show one tooltip at a time
                    },
                    hide: {
                        event: false
                    },
                    style: {
                        classes: 'uploadqtip ui-tooltip-light ui-tooltip-shadow ui-tooltip-rounded'
                    }
                });
            $('a.sharingLink').click(
                function(){
                    loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
                    showDialog(json.browse.share);
                });
            $('div.viewAction ul').fadeIn('fast');
        });
};

midas.createInfo = function (jsonContent) {
    var arrayElement = jQuery.parseJSON(jsonContent);
    var html='';
    if(arrayElement['type']=='community') {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/community-big.png" />';
    }
    else if(arrayElement['type']=='folder') {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/folder-big.png" />';
    }
    else {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/document-big.png" />';
    }

    html+='<span class="infoTitle" >'+sliceFileName(arrayElement['name'],27)+'</span>';
    html+='<table>';
    html+='  <tr>';
    html+='    <td>'+arrayElement.translation.Created+'</td>';
    html+='    <td>'+arrayElement.creation+'</td>';
    html+='  </tr>';
    if(arrayElement['type']=='community') {
        html+='  <tr>';
        html+='    <td>Members';
        html += '</td>';
        html+='    <td>'+arrayElement['members']+'</td>';
        html+='  </tr>';
    }
    if(arrayElement['type']=='item') {
        if(arrayElement['norevisions'] == true) {
            html+='  <tr>';
            html+='    <td>No Revisions</td>';
            html+='  </tr>';
            html+='  <tr>';
        }
        else {
            html+='  <tr>';
            html+='    <td>'+arrayElement.translation.Uploaded+'</td>';
            html+='    <td><a href="'+json.global.webroot+'/user/'+arrayElement['uploaded']['user_id']+'">'+arrayElement['uploaded']['firstname']+' '+arrayElement['uploaded']['lastname']+'</a></td>';
            html+='  </tr>';
            html+='  <tr>';
            html+='    <td>Revisions</td>';
            html+='    <td>'+arrayElement['revision']['revision']+'</td>';
            html+='  </tr>';
            html+='  <tr>';
            html+='    <td>Files</td>';
            html+='    <td>'+arrayElement['nbitstream']+'</td>';
            html+='  </tr>';
            html+='  </tr>';
            html+='    <td>Size</td>';
            html+='    <td>'+arrayElement['sizebytes']+' B</td>';
            html+='  </tr>';
        }
    }

    if(arrayElement['type']=='folder') {
        html+='  <tr>';
        html+='    <td>Last Updated</td>';
        html+='    <td>'+arrayElement['updated']+'</td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>Size</td>';
        html+='    <td>'+arrayElement['sizebytes']+' B</td>';
        html+='  </tr>';
    }
    html+='</table>';
    if(arrayElement['type']=='community'&&arrayElement['privacy']==2) {
        html+='<h4>'+arrayElement.translation.Private+'</h4>';
    }

  if(arrayElement['thumbnail']!=undefined&&arrayElement['thumbnail']!='')
    {
    html+='<h1>'+json.browse.preview+'</h1><a href="'+json.global.webroot+'/item/'+arrayElement['item_id']+'"><img class="infoLogo" alt="" src="'+json.global.webroot+'/'+arrayElement['thumbnail']+'" /></a>';
    }

    $('div.ajaxInfoElement').html(html);
};

/**
 * Enable selecting all of the elements in a treeview browser
 * @param opts an object with an optional callback
 */
midas.browser.enableSelectAll = function (opts) {
    var default_args = { callback: midas.genericCallbackCheckboxes };
    var options = $.extend({}, default_args, opts);

    // Select/deslect all rows. If we are doing deselect all, we include
    // hidden rows
    $('#browseTableHeaderCheckbox').click(
        function() {
            var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
            $('#browseTable').find(selector).prop("checked", this.checked);
            options.callback($('#browseTable'));
        });
};

midas.enableRangeSelect = function (node) {
    $('input.treeCheckbox:visible').enableCheckboxRangeSelection(
        {
            onRangeSelect: function() {
                midas.genericCallbackCheckboxes($('#browseTable'));
            }
        });
};

midas.cutName = function(name, nchar) {  if(name.length>nchar)      {      name=name.substring(0,nchar)+'...';      return name;      }  return name;  }
