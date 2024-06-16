//Overrides core js to supplement changes to the file manager HTML/PHP code
window.onload = function(){

    //custom launch sequence (using the custom packaged tool URL)
    ccm_launchFileManager = function(filters) {
        $.fn.dialog.open({
            width: '90%',
            height: '70%',
            appendButtons: true,
            modal: false,
            href: "/index.php/tools/files/search_dialog?ocID=" + CCM_CID + "&search=1" + filters,
            title: ccmi18n_filemanager.title
        });
    }

    //custom file set submit form (to prevent the file manager closing out after a file is added to set(s))
    ccm_alSubmitSetsForm = function(searchInstance) {
        ccm_deactivateSearchResults(searchInstance);
        $("#ccm-" + searchInstance + "-add-to-set-form").ajaxSubmit(function(resp) {	
            $("#ccm-" + searchInstance + "-advanced-search").ajaxSubmit(function(resp) {
                $("#ccm-" + searchInstance + "-sets-search-wrapper").load(CCM_TOOLS_PATH + '/files/search_sets_reload', {'searchInstance': searchInstance}, function() {
                    $(".chosen-select").chosen(ccmi18n_chosen);
                    ccm_parseAdvancedSearchResponse(resp, searchInstance);
                });
            });
        });
    }
};
