<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="shortcut icon" type="image/ico" href="http://www.datatables.net/favicon.ico">
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=2.0">
	<title>Mpesa Transactions</title>
	<link rel="stylesheet" type="text/css" href="media/css/semantic.min.css">
	<link rel="stylesheet" type="text/css" href="media/css/dataTables.semanticui.css">
	<link rel="stylesheet" type="text/css" href="media/css/buttons.dataTables.min.css">
	<style type="text/css" class="init"></style>
	<script type="text/javascript" language="javascript" src="media/js/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="media/js/jquery.dataTables.js"></script>
	<script type="text/javascript" language="javascript" src="media/js/dataTables.semanticui.js"></script>
	<script type="text/javascript" language="javascript" src="media/js/semantic.min.js"></script>
    <script type="text/javascript" language="javascript" src="media/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" language="javascript" src="media/js/jszip.min.js"></script>
    <script type="text/javascript" language="javascript" src="media/js/pdfmake.min.js"></script>
    <script type="text/javascript" language="javascript" src="media/js/vfs_fonts.js"></script>
    <script type="text/javascript" language="javascript" src="media/js/buttons.html5.min.js"></script>

    <script type="text/javascript" language="javascript" class="init">

        var dataSet = [
            <?php
                $count = 0;
                foreach ($mpesaTransactions as $mpesaTransaction){
                    $row = "";
                    if($count>0){
                        $row = ",";
                    }
                    $row .='[' ;
                    $row .='"'. $mpesaTransaction->phonenumber.'",';
                    $row .='"'. $mpesaTransaction->name.'",';
                    $row .='"'. $mpesaTransaction->amount.'",';
                    $row .='"'. $mpesaTransaction->receipt.'",';
                    $row .='"'. $mpesaTransaction->amount_posted.'",';
                    $row .='"'. $mpesaTransaction->mifos_account_number.'",';
                    $row .='"'. $mpesaTransaction->time.'"';
                    $row .=']';
                    echo $row;
                    $count++;
                }
            ?>

        ];

        $(document).ready(function() {
            $('#mpesaTransactions').DataTable({
                dom: 'Blfrtip',
                data: dataSet,
                columns: [
                    { title: "Phone Number" },
                    { title: "Client" },
                    { title: "Amount Received" },
                    { title: "Transaction NO." },
                    { title: "Amount Posted" },
                    { title: "Account No." },
                    { title: "Date" }
                ],
                buttons: [
                    {
                        extend: 'copyHtml5',
                        exportOptions: {
                            columns: ':contains("Office")'
                        }
                    },
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5'
                ],
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
            });
        } );

	</script>
</head>
<body class="dt-example dt-example-semanticui">

	<div class="container">
		<section>
			<h1>Mpesa Transactions <span></span></h1>
			<div class="demo-html"></div>
			<table id="mpesaTransactions" class="compact ui celled table order-column hover" cellspacing="0" width="100%"></table>
		</section>
	</div>
</body>
</html>