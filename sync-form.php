<div class="container mt-3 pt-3">
    <div class="row">
        <div class="col-md-6 order-md-2">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="text-center mb-4">Live Report</h5>
                    <button id="generate_report" class="btn btn-primary w-100 mb-4">Generate Live Report</button>
                </div>
            </div>
            <div class="row report-nodata">
                <div class="col-12">
                    <p class="text-center">Generate the live report to view report details</p>
                </div>
            </div>
            <div class="row d-none report-data">
                <div class="col-sm-4">
                    <div class="border p-4">
                        <h6 class="text-center">Teller 1 Total</h6>
                        <h3 class="text-center" id="teller_1_report"></h3>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border p-4">
                        <h6 class="text-center">Teller 2 Total</h6>
                        <h3 class="text-center" id="teller_2_report"></h3>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border p-4">
                        <h6 class="text-center">Combined Total</h6>
                        <h3 class="text-center" id="teller_total_report"></h3>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        <div class="col-md-6 order-md-1">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="text-center mb-4">Sync Today's Live Data</h5>
                    <button id="sync_live_submit" class="btn btn-primary w-100 mb-4">Sync Today's Data</button>
                </div>
            </div>
            <div class="row my-4">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col"><hr></div>
                        <div class="col-auto">OR</div>
                        <div class="col"><hr></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h5 class="text-center mb-4">Sync by Date</h5>
                    <input id="sync_date" name="sync_date" class="form-control mb-4" type="date"/>
                    <button id="sync_date_submit" class="btn btn-primary w-100 mb-4">Start Sync</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="spinner-overlay d-none">
    <h3>Please Wait...</h3><br/>
    <div class="spinner-border" role="status"></div>
</div>
<div class="position-fixed toast-container bottom-0 end-0 p-3">
    <div data-bs-delay="4000" class="toast align-items-center text-bg-success position-relative border-0" role="alert" aria-live="assertive" id="report_toast" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">Report Generated</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div data-bs-delay="4000" class="toast align-items-center text-bg-success position-relative border-0" role="alert" aria-live="assertive" id="sync_success_toast" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">Sync Completed</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div data-bs-delay="4000" class="toast align-items-center text-bg-danger position-relative border-0" role="alert" aria-live="assertive" id="error_toast" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">Error: Check the log for details</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="container mb-5 pb-5">
    <div class="row">
        <div class="col-md-12">
            <hr>
            <h5 class="text-center">Log</h5>
            <pre id="log_view"></pre>
        </div>
    </div>
</div>
<style>
    pre {
        background-color: #f0f0f0;
        min-height: 400px;
        height: 400px;
        resize: both;
        width: 100%;
        overflow: scroll;
        font-size: 11px;
    }
    .spinner-overlay {
        background-color: rgba(255,255,255,.8);
        display:flex;
        z-index:999;
        flex-direction: column;
        position: fixed;
        align-items:center;
        justify-content: center;
        height: 100vh;
        width: 100vw;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
    }
</style>
<script>
    $('#sync_live_submit').on('click', function(e) {
        $('.spinner-overlay').removeClass('d-none');
        // - 14400 (4 hours) for EST Timezone correction
        var timestamp = Math.floor(Date.now() / 1000 - 14400); 
        $.ajax({
            url: "data-sync.php",
            type: "GET",
            data: {
                datetime: timestamp,
                live: 1
            },
            success: function (response) {
                var result = JSON.parse(response);
                $('#log_view').html(result.log);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('sync_success_toast'))
                toast.show()
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $('#log_view').html(thrownError);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('error_toast'))
                toast.show()
            }
        }); // END AJAX
    });
    $('#generate_report').on('click', function(e) {
        $('.spinner-overlay').removeClass('d-none');
        // - 14400 (4 hours) for EST Timezone correction
        var timestamp = Math.floor(Date.now() / 1000 - 14400); 
        $.ajax({
            url: "data-sync.php",
            type: "GET",
            data: {
                datetime: timestamp,
                live: 1,
                report: 1
            },
            success: function (response) {
                var result = JSON.parse(response);

                var teller_1_total = '$' + result.data.teller_1_rev;
                var teller_2_total = '$' + result.data.teller_2_rev;
                var teller_total = '$' + result.data.total_teller_rev;

                $('.report-nodata').addClass('d-none');
                $('.report-data').removeClass('d-none');

                $('#teller_1_report').html(teller_1_total);
                $('#teller_2_report').html(teller_2_total);
                $('#teller_total_report').html(teller_total);

                $('#log_view').html(result.log);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('report_toast'))
                toast.show()
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $('#log_view').html(thrownError);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('error_toast'))
                toast.show()
            }
        }); // END AJAX
    });
    $('#sync_date_submit').on('click', function(e) {
        $('.spinner-overlay').removeClass('d-none');
        var date = new Date($('#sync_date').val());
        // + 43200 (12 hours) for midday
        var timestamp = Math.floor(date.getTime() / 1000 + 43200);
        $.ajax({
            url: "data-sync.php",
            type: "GET",
            data: {
                datetime: timestamp
            },
            success: function (response) {
                var result = JSON.parse(response);
                $('#log_view').html(result.log);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('sync_success_toast'))
                toast.show()
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $('#log_view').html(thrownError);
                $('.spinner-overlay').addClass('d-none');
                const toast = new bootstrap.Toast(document.getElementById('report_toast'))
                toast.show()
            }
        }); // END AJAX
    });
</script>