$(document).ready(function () {
    Dropzone.autoDiscover = false;
    var myDropzone = new Dropzone("div#dropzoneFileUpload", {
        paramName: "file",
        maxFilesize: 20,
        uploadMultiple: true,
        addRemoveLinks: true,
        url: "/file/upload",
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(file, response){
            location.reload();
        },
        init: function() {
            this.on('error', function(file, errorMessage) {
                var errorDisplay = document.querySelectorAll('[data-dz-errormessage]');
                errorDisplay[errorDisplay.length - 1].innerHTML = errorMessage['file.0'];
            });
        }
    });

    $(document).on("click", ".btn-view", function() {
        var file = $(this).val();
        var container = $('#ajax-data').html('');

        $.ajax({
            type: "POST",
            url: "/xlsx/get-data/"+file,
            data: {filename:file},
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response) {
                    container.html(response);
                    $('#ajax-data table').addClass('table table-border');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert("An error occurred while retrieving the data: " + errorThrown);
            }
        });
    });

    $(document).on("click", "#previewHide", function() {
        $('#ajax-data').html('');
    });
});