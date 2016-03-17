<?php
/************************************************
 **************** AJAX REQUESTS *****************
 ************************************************/

$delay = (isset($_GET['delay']) ? $_GET['delay'] : 2);
$folder = (isset($_GET['folder']) ? $_GET['folder'] : '/Applications/MAMP/htdocs/git/laravelequips');

if (isset($_POST['update'])) {
    $headPath = $_POST["headpath"];
    $gitPullCmd = `cd {$headPath}; git pull`;

    if (!$gitPullCmd) {
        echo '<span class="alert-danger">Repo could not found</span>';
    } elseif (substr($gitPullCmd, 0, 7) == 'Already') {
        echo '<span class="alert-info">Repo already up to date</span>';
    } elseif (substr($gitPullCmd, 0, 8) == 'CONFLICT') {
        echo '<span class="alert-danger">Conflict error occurred</span>';
    } elseif (substr($gitPullCmd, 0, 8) == 'Updating' || substr($gitPullCmd, 0, 8) == 'Fetching') {
        echo '<span class="alert-success">Repo updated successfully</span>';
    } else {
        echo '<span class="alert-danger">Unknows issue happened</span>';
    }

    exit;
}

/************************************************
 ****************** MAIN PAGE *******************
 ************************************************/
?>
<!doctype html>
<html>
<head>
    <title></title>

    <link rel="stylesheet" href="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/css/bootstrap.css" crossorigin="anonymous">
    <style type="text/css">
        td {
            font-size: 14px;
        }
        div#preloader {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 999;
            width: 100%;
            height: 100%;
            overflow: visible;
            background: rgba(51, 51, 51, 0.8) url('//sierrafire.cr.usgs.gov/images/loading.gif') no-repeat center center;
        }

    </style>

    <script type="text/javascript" src="https://code.jquery.com/jquery-2.2.1.min.js"></script>
    <script src="tether-1.2.0/dist/js/tether.min.js"></script>
    <script src="https://cdn.rawgit.com/twbs/bootstrap/v4-dev/dist/js/bootstrap.js" crossorigin="anonymous"></script>


    <script type="text/javascript">
        jQuery(function($) {
            $(window).load(function(){
                $('#preloader').fadeOut('slow', function(){
                    // $('#preloader').style("display", "none");
                });
            });

            // Update all repos
            $('#update-all').click(function(){
                $('#update-all').html('In progress..').addClass("disabled");

                $(".update-single").each(function(i, el) {
                    setTimeout(function(){
                        updateSingle(el);
                    }, 500 + (i * <?= $delay * 1000 ?>));
                }).promise().done(function(){
                    // TODO: Fix this
                    // $('#update-all').html('OK');
                });
            });

        });

        // Update single repo
        function updateSingle(which) {
            var headpath = $(which).closest("tr").data("headpath");

            $(which).html('In progress..').addClass("disabled");

            var single_request = $.ajax({
                url: "gitUpdater.php",
                type: "POST",
                data: {
                    update: true,
                    headpath: headpath
                },
                dataType: "html"
            }).done(function(data) {
                $(which).closest("tr").find(".process-status").html(data);
                $(which).html('OK');
            }).fail(function(jqXHR, textStatus) {
                $(which).closest("tr").find(".process-status").html(data);
                $(which).html('OK');
            });
        }
    </script>

</head>
<body>

<div id="preloader"></div>

<br><br>
<div class="col-md-12">
    <div class="row">

        <div class="col-md-8">
            <form class="form-inline">
                <div class="form-group">
                    <label for="folder">Search path: </label>
                    <input type="text" 
                        class="form-control" 
                        id="folder" 
                        name="folder" 
                        value="<?= $folder ?>" 
                        placeholder="Folder name or full path">
                </div>
                <div class="form-group">
                    <label for="delay">Delay: </label>
                    <input type="number" 
                        class="form-control" 
                        id="delay" 
                        name="delay" 
                        value="<?= $delay ?>" 
                        placeholder="Delay between starts">
                </div>

                <button type="submit" class="btn btn-default">Apply</button>
            </form>
        </div>

        <div class="col-md-4">
            <button type="button" id="update-all" class="btn btn-block btn-primary-outline">Update All</button>
        </div>

    </div>
    <?php
        $count = 1;
        $head = dirname(__FILE__).'/';

        try {
            $iteration = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (UnexpectedValueException $e) {
            echo '
            </div>
            <!-- Modal -->
            <div class="modal fade" id="exc" tabindex="-1" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">Exception</h4>
                        </div>
                        <div class="modal-body">
                            <p>'.$e->getMessage().'</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                jQuery(function($){
                    $("#exc").modal("show");
                });
            </script>
            </body></html>
            ';
            exit;
        }
    ?>

    <table class="table table-sm" id="response" style="margin-top: 25px">
        <thead>
            <tr>
                <th>#</th>
                <th>Local Path</th>
                <th>Origin URL</th>
                <th>Update?</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($iteration as $path => $dir) {
                if ($dir->isDir() && substr($path, -4) == '.git') {
                    if (substr($path, 0, 1) <> '/') {
                        $headPath = $head.substr($path, 0, -4);
                    } else {
                        $headPath = substr($path, 0, -4);
                    }
                    $originUrl = `cd {$headPath}; git config --get remote.origin.url`;
        ?>
            <tr data-headpath="<?= realpath($headPath) ?>">
                <th scope="row"><?= $count ?></th>
                <td><?= realpath($path) ?></td>
                <td><?= '<a href="'.trim($originUrl).'" target="_blank">'.trim($originUrl).'</a>' ?></td>
                <td><a class="btn btn-info-outline update-single" onclick="return updateSingle(this);" href="javascript:;">Update</a></td>
                <td class="process-status">Pending</td>
            </tr>
        <?
                    $count++;
                }
            }
        ?>
        </tbody>
    </table>
</div>



</body>
</html>
