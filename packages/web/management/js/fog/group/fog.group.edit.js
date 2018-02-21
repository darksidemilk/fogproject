(function($) {
    // ---------------------------------------------------------------
    // GENERAL TAB
    var originalName = $('#name').val(),
        updateName = function(newName) {
            var e = $('#pageTitle'),
                text = e.text();
            text = text.replace(': ' + originalName, ': ' + newName);
            document.title = text;
            e.text(text);
        };

    $('#productKey').inputmask({mask: Common.masks.productKey});

    var generalForm = $('#group-general-form'),
        generalFormBtn = $('#general-send'),
        generalDeleteBtn = $('#general-delete'),
        resetEncryptionBtn = $('#reset-encryption-data'),
        resetEncryptionModal = $('#resetencryptionmodal'),
        resetEncryptionCancelBtn = $('#resetencryptionCancel'),
        resetEncryptionConfirmBtn = $('#resetencryptionConfirm');

    generalForm.on('submit',function(e) {
        e.preventDefault();
    });
    generalFormBtn.on('click',function() {
        generalFormBtn.prop('disabled', true);
        generalDeleteBtn.prop('disabled', true);
        Common.processForm(generalForm, function(err) {
            generalFormBtn.prop('disabled', false);
            generalDeleteBtn.prop('disabled', false);
            if (err) {
                return;
            }
            updateName($('#name').val());
            originalName = $('#name').val();
        });
    });
    generalDeleteBtn.on('click',function() {
        generalFormBtn.prop('disabled', true);
        generalDeleteBtn.prop('disabled', true);
        Common.massDelete(null, function(err) {
            if (err) {
                generalDeleteBtn.prop('disabled', false);
                generalFormBtn.prop('disabled', false);
                return;
            }
            window.location = '../management/index.php?node='+Common.node+'&sub=list';
        });
    });

    // Reset encryption confirmation modal.
    resetEncryptionBtn.on('click', function(e) {
        e.preventDefault();
        // Set our general form buttons disabled.
        $(this).prop('disabled', true);
        generalFormBtn.prop('disabled', true);
        generalDeleteBtn.prop('disabled', true);

        // Enable our modal buttons.
        resetEncryptionConfirmBtn.prop('disabled', false);
        resetEncryptionCancelBtn.prop('disabled', false);

        // Display the reset encryption modal
        resetEncryptionModal.modal('show');
    });

    // Modal cancelled
    resetEncryptionCancelBtn.on('click', function(e) {
        e.preventDefault();

        // Set our modal buttons disabled.
        $(this).prop('disabled', true);
        resetEncryptionConfirmBtn.prop('disabled', true);

        // Enable our general form buttons.
        generalFormBtn.prop('disabled', false);
        generalDeleteBtn.prop('disabled', false);
        resetEncryptionBtn.prop('disabled', false);

        // Hide the modal
        resetEncryptionModal.modal('hide');
    });

    // Modal Confirmed
    resetEncryptionConfirmBtn.on('click', function(e) {
        e.preventDefault();

        // Set our modal buttons disabled.
        $(this).prop('disabled', true);
        resetEncryptionCancelBtn.prop('disabled', true);

        // Enable our general form buttons.
        generalFormBtn.prop('disabled', false);
        generalDeleteBtn.prop('disabled', false);
        resetEncryptionBtn.prop('disabled', false);

        // Reset our encryption data.
        $.post(
            '../management/index.php?sub=slearAES',
            {groupid: Common.id}
        );

        // Hide the modal
        resetEncryptionModal.modal('hide');
    });

    // ---------------------------------------------------------------
    // ACTIVE DIRECTORY TAB
    var ADForm = $('#active-directory-form'),
        ADFormBtn = $('#ad-send'),
        ADClearBtn = $('#ad-clear');

    ADForm.on('submit',function(e) {
        e.preventDefault();
    });
    ADFormBtn.on('click', function() {
        ADFormBtn.prop('disabled', true);
        ADClearBtn.prop('disabled', true);
        Common.processForm(ADForm, function(err) {
            ADFormBtn.prop('disabled', false);
            ADClearBtn.prop('disabled', false);
        });
    });
    ADClearBtn.on('click', function() {
        ADClearBtn.prop('disabled', true);
        ADFormBtn.prop('disabled', true);

        var restoreMap = [];
        ADForm.find('input[type="text"], input[type="password"], textarea').each(function(i, e) {
            restoreMap.push({checkbox: false, e: e, val: $(e).val()});
            $(e).val('');
            $(e).prop('disabled', true);
        });
        ADForm.find('input[type="checkbox"]').each(function(i, e) {
            restoreMap.push({checkbox: true, e: e, val: $(e).iCheck('update')[0].checked});
            $(e).iCheck('uncheck');
            $(e).iCheck('disable');
        });

        ADForm.find('input[type="text"], input[type="password"], textarea').val('');
        ADForm.find('input[type="checkbox"]').iCheck('uncheck');

        Common.processForm(ADForm, function(err) {
            for (var i = 0; i < restoreMap.length; i++) {
                field = restoreMap[i];
                if (field.checkbox) {
                    if (err) $(field.e).iCheck((field.val ? 'check' : 'uncheck'));
                    $(field.e).iCheck('enable');
                } else {
                    if (err) $(field.e).val(field.val);
                    $(field.e).prop('disabled', false);
                }
            }
            ADClearBtn.prop('disabled', false);
            ADFormBtn.prop('disabled', false);
        });
    });

    // ---------------------------------------------------------------
    // PRINTER TAB
    var printerConfigForm = $('#printer-config-form'),
        printerConfigBtn = $('#printer-config-send'),
        printerAddBtn = $('#printer-add'),
        printerDefaultBtn = $('#printer-default'),
        printerRemoveBtn = $('#printer-remove'),
        DEFAULT_PRINTER_ID = -1;

    printerAddBtn.prop('disabled', true);
    printerRemoveBtn.prop('disabled', true);

    function onPrintersToAddTableSelect (selected) {
        var disabled = selected.count() == 0;
        printerAddBtn.prop('disabled', disabled);
    }
    function onPrintersSelect (selected) {
        var disabled = selected.count() == 0;
        printerAddBtn.prop('disabled', disabled);
        printerRemoveBtn.prop('disabled', disabled);
    }

    var printersTable = Common.registerTable($('#group-printers-table'), onPrintersSelect, {
        order: [
            [1, 'asc']
        ],
        columns: [
            {data: 'isDefault'},
            {data: 'name'},
            {data: 'config'}
        ],
        rowId: 'id',
        columnDefs: [
            {
                responsivePriority: -1,
                render: function(data, type, row) {
                    var checkval = '';
                    if (row.isDefault) {
                        checkval = ' checked';
                    }
                    return '<div class="radio">'
                        + '<input belongsto="defaultPrinters" type="radio" class="default" name="default" id="printer_'
                        + row.id
                        + '" value="' + row.id + '"'
                        + ' wasoriginaldefault="'
                        + checkval
                        + '"'
                        + checkval
                        + '/>'
                        + '</div>'
                },
                targets: 0,
            },
            {
                responsivePriority: 0,
                render: function(data, type, row) {
                    return '<a href="../management/index.php?node=printer&sub=edit&id=' + row.id + '">' + data + '</a>';
                },
                targets: 1
            },
            {
                render: function(data, type, row) {
                    return row.config == 'Local' ? 'TCP/IP' : row.config;
                },
                targets: 2
            },
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: '../management/index.php?node='+Common.node+'&sub=getPrintersList&id='+Common.id,
            type: 'post'
        }
    });

    printersTable.on('draw', function() {
        Common.iCheck('#group-printers input');
        $('.default').on('ifClicked', onRadioSelect);
    });
    printerDefaultBtn.prop('disabled', true);

    var onRadioSelect = function(event) {
        if ($(this).attr('belongsto') === 'defaultPrinters') {
            var id = parseInt($(this).val());
            if (DEFAULT_PRINTER_ID === -1 && $(this).attr('wasoriginaldefault') === ' checked') {
                DEFAULT_PRINTER_ID = id;
            }
            if (id === DEFAULT_PRINTER_ID) {
                $(this).iCheck('uncheck');
                DEFAULT_PRINTER_ID = 0;
            } else {
                DEFAULT_PRINTER_ID = id;
            }
            printerDefaultBtn.prop('disabled', false);
        }
    };

    // Setup default printer watcher
    $('.default').on('ifClicked', onRadioSelect);

    printerDefaultBtn.on('click', function() {
        printerAddBtn.prop('disabled', true);
        printerRemoveBtn.prop('disabled', true);

        var method = printerDefaultBtn.attr('method'),
            action = printerDefaultBtn.attr('action'),
            opts = {
                'defaultsel': '1',
                'default': DEFAULT_PRINTER_ID
            };
        Common.apiCall(method,action,opts,function(err) {
            printerDefaultBtn.prop('disabled', !err);
            onPrintersSelect(printersTable.rows({selected: true}));
        });
    });

    printerConfigForm.serialize2 = printerConfigForm.serialize;
    printerConfigForm.serialize = function() {
        return printerConfigForm.serialize2() + '&levelup';
    };
    printerConfigForm.on('submit',function(e) {
        e.preventDefault();
    });
    printerConfigBtn.on('click', function() {
        printerConfigBtn.prop('disabled', true);
        Common.processForm(printerConfigForm, function(err) {
            printerConfigBtn.prop('disabled', false);
        });
    });
    printerAddBtn.on('click', function() {
        printerAddBtn.prop('disabled', true);

        var method = printerAddBtn.attr('method'),
            action = printerAddBtn.attr('action'),
            rows = printersToAddTable.rows({selected: true}),
            toAdd = Common.getSelectedIds(printersToAddTable),
            opts = {
                'updateprinters': '1',
                'printer': toAdd
            };

        Common.apiCall(method,action,opts,function(err) {
            if (!err) {
                printersTable.draw(false);
                printersTable.rows({
                    selected: true
                }).remove().draw(false);
                printersTable.rows({selected: true}).deselect();
            } else {
                printerAddBtn.prop('disabled', false);
            }
        });
    });

    printerRemoveBtn.on('click',function() {
        printerAddBtn.prop('disabled', true);
        printerRemoveBtn.prop('disabled', true);
        printerDefaultBtn.prop('disabled', true);

        var method = printerRemoveBtn.attr('method'),
            action = printerRemoveBtn.attr('method'),
            rows = printersTable.rows({selected: true}),
            toRemove = Common.getSelectedIds(printersTable),
            opts = {
                'printdel': '1',
                'printerRemove': toRemove
            };

        Common.apiCall(method,action,opts, function(err) {
            printerDefaultBtn.prop('disabled', false);
            if (!err) {
                printersTable.draw(false);
                printersTable.rows({
                    selected: true
                }).remove().draw(false);
                printersTable.rows({selected: true}).deselect();
            } else {
                printerRemoveBtn.prop('disabled', false);
            }
        });
    });

    if (Common.search && Common.search.length > 0) {
        printersTable.search(Common.search).draw();
    }

    // ---------------------------------------------------------------
    // SNAPINS TAB
    var snapinsAddBtn = $('#snapins-add'),
        snapinsRemoveBtn = $('#snapins-remove');

    snapinsAddBtn.prop('disabled', true);
    snapinsRemoveBtn.prop('disabled', true);

    function onSnapinsRemoveSelect (selected) {
        var disabled = selected.count() == 0;
        snapinsRemoveBtn.prop('disabled', disabled);
    }
    function onSnapinsAddSelect (selected) {
        var disabled = selected.count() == 0;
        snapinsAddBtn.prop('disable', disabled);
    }

    var snapinsTable = Common.registerTable($('#group-snapins-table'), onSnapinsRemoveSelect, {
        columns: [
            {data: 'name'},
            {data: 'createdTime'}
        ],
        rowId: 'id',
        columnDefs: [
            {
                responsivePriority: -1,
                render: function(data, type, row) {
                    return '<a href="../management/index.php?node=snapin&sub=edit&id=' + row.id + '">' + data + '</a>';
                },
                targets: 0
            }
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: '../management/index.php?node='+Common.node+'&sub=getSnapinsList&id='+Common.id,
            type: 'post'
        }
    });
    snapinsTable.on('draw', function() {
        Common.iCheck('#group-snapins-table input');
    });

    snapinsAddBtn.on('click', function() {
        snapinsAddBtn.prop('disabled', true);
        var method = snapinsAddBtn.attr('method'),
            action = snapinsAddBtn.attr('action'),
            rows = snapinsTable.rows({selected: true}),
            toAdd = Common.getSelectedIds(snapinsTable),
            opts = {
                'updatesnapins': '1',
                'snapin': toAdd
            };
        Common.apiCall(method,action,opts,function(err) {
            if (!err) {
                snapinsTable.draw(false);
                snapisnTable.rows({
                    selected: true
                }).remove().draw(false);
                snapinsTable.rows({selected: true}).deselect();
            } else {
                snapinsAddBtn.prop('disabled', false);
            }
        });
    });

    snapinsRemoveBtn.on('click', function() {
        snapinsRemoveBtn.prop('disable', true);
        var method = snapinsRemoveBtn.attr('method'),
            action = snapinsRemoveBtn.attr('action'),
            rows = snapinsTable.rows({selected: true}),
            toRemove = Common.getSelectedIds(snapinsTable),
            opts = {
                'snapdel': '1',
                'snapinRemove': toRemove
            };
        Common.apiCall(method,action,opts,function(err) {
            if (!err) {
                snapinsTable.draw(false);
                snapinsTable.rows({
                    selected: true
                }).remove().draw(false);
                snapinsTable.rows({selected: true}).deselect();
            } else {
                snapinsRemoveBtn.prop('disabled', false);
            }
        });
    });
    if (Common.search && Common.search.length > 0) {
        snapinsTable.search(Common.search).draw();
    }

    // ---------------------------------------------------------------
    // SERVICE TAB
    var modulesEnableBtn = $('#modules-enable'),
        modulesDisableBtn = $('#modules-disable'),
        modulesUpdateBtn = $('#modules-update'),
        modulesDispBtn = $('#displayman-send'),
        modulesAloBtn = $('#alo-send');

    function onModulesDisable(selected) {
        var disabled = selected.count() == 0;
        modulesDisableBtn.prop('disabled', disabled);
    }
    function onModulesEnable(selected) {
        var disabled = selected.count() != 0;
        modulesEnableBtn.prop('disabled', disabled);
    }

    var modulesTable = Common.registerTable($("#modules-to-update"), onModulesEnable, {
        columns: [
            {data: 'name'}
        ],
        rowId: 'id',
        columnDefs: [
            {
                responsivePriority: -1,
                render: function(data, type, row) {
                    return row.name
                },
                targets: 0
            },
            {
                render: function(data, type, row) {
                    return '<div class="checkbox">'
                    + '<input type="checkbox" class="associated" name="associate[]" id="moduleAssoc_'
                    + row.id
                    + '" value="'
                    + row.id
                    + '"/>'
                    + '</div>';
                },
                targets: 1
            }
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: '../management/index.php?node='+Common.node+'&sub=getModulesList&id='+Common.id,
            type: 'post'
        }
    });
    modulesTable.on('draw', function() {
        Common.iCheck('#modules-to-update input');
    });

    modulesUpdateBtn.on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true);
        var method = modulesUpdateBtn.attr('method'),
            action = modulesUpdateBtn.attr('action'),
            toEnable = [],
            toDisable = [],
            opts = {
                'enablemodulessel': '1',
                'disablemodulessel': '1',
                'enablemodules': toEnable,
                'disablemodules': toDisable
            };
        $('#modules-to-update').find('.associated').each(function() {
            if ($(this).is(':checked')) {
                toEnable.push($(this).val());
            } else if (!$(this).is(':checked')) {
                toDisable.push($(this).val());
            }
        });
        Common.apiCall(method,action,opts,function(err) {
            modulesUpdateBtn.prop('disabled', false);
            if (!err) {
                modulesTable.draw(false);
                modulesTable.rows({selected: true}).deselect();
            }
        });
    });
    modulesEnableBtn.on('click', function(e) {
        e.preventDefault();
        $('#modules-to-update_wrapper .buttons-select-all').trigger('click');
        $('#modules-to-update_wrapper .associated').iCheck('cleck');
        $(this).prop('disabled', true);
        modulesDisableBtn.prop('disabled', false);
        var method = modulesEnableBtn.attr('method'),
            action = modulesEnableBtn.attr('action'),
            rows = modulesTable.rows({selected: true}),
            toEnable = Common.getSelectedIds(modulesTable),
            opts = {
                'enablemodulessel': '1',
                'enablemodules': toEnable
            };
        Common.apiCall(method,action,opts,function(err) {
            if (!err) {
                $('#modules-to-update').find('.associated').each(function() {
                    if (toEnable.indexOf($(this).val()) != -1) {
                        $(this).iCheck('check');
                    }
                });
            } else {
                modulesEnableBtn.prop('disabled', false);
            }
            modulesTable.draw(false);
            modulesTable.rows({selected: true}).deselect();
        });
    });
    modulesDisableBtn.on('click', function(e) {
        e.preventDefault();
        $('#modules-to-update_wrapper .buttons-select-none').trigger('click');
        $('#modules-to-update_wrapper .associated').iCheck('uncheck');
        $(this).prop('disabled', true);
        modulesEnableBtn.prop('disabled', false);
        var method = modulesDisableBtn.attr('method'),
            action = modulesDisableBtn.attr('action'),
            rows = modulesTable.rows({selected: true}),
            toDisable = [],
            opts = {
                'disablemodulessel': '1',
                'disablemodules': toDisable
            };
        $('#modules-to-update').find('.associated').each(function() {
            if (!$(this).is(':checked')) {
                toDisable.push($(this).val());
            }
        });
        Common.apiCall(method,action,opts,function(err) {
            if (!err) {
                $('#modules-to-update').find('.associated').each(function() {
                    if (toDisable.indexOf($(this).val()) != -1) {
                        $(this).iCheck('uncheck');
                    }
                });
            } else {
                modulesDisableBtn.prop('disabled', false);
            }
            modulesTable.draw(false);
            modulesTable.rows({selected: true}).deselect();
        });
    });
    modulesDispBtn.on('click', function(e) {
        e.preventDefault();
        var form = $('#group-dispman');
        modulesDispBtn.prop('disabled', true);
        Common.processForm(form, function(err) {
            modulesDispBtn.prop('disabled', false);
        });
    });
    modulesAloBtn.on('click', function(e) {
        e.preventDefault();
        var form = $('#group-alo');
        modulesDispBtn.prop('disabled', true);
        Common.processForm(form, function(err) {
            modulesAloBtn.prop('disabled', false);
        });
    });
    if (Common.search && Common.search.length > 0) {
        modulesTable.search(Common.search).draw();
    }
})(jQuery)
