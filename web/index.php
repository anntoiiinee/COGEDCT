<?php
	/*
	@author haxom <haxom@haxom.net>
	@version 1.1
	*/

    // start session
    session_start();

	// import PhpModbus
	require_once './phpmodbus/Phpmodbus/ModbusMaster.php';
	$modbus = new ModbusMaster("127.0.0.1", "TCP");
	$modbus->port = "502";
	$modbus->client_port = "502";
	$unitId = 66; // 0x42

	if(isset($_GET['act']) && !empty($_GET['act']))
	{
		$act = $_GET['act'];

		switch($act)
		{
			case "readAll":
				$output = Array();
				$output['coils'] = $modbus->readCoils($unitId, 0, 2);
				$output['broken'] = $modbus->readCoils($unitId, 25, 1);
				$registers = array_chunk($modbus->readMultipleRegisters($unitId, 0, 2), 2);
				foreach($registers as $key => $value)
					$output['registers'][$key] = PhpType::bytes2unsignedInt($value);
				print json_encode($output);
				exit();

			case "manualStop":
                if(isset($_SESSION['auth']) && $_SESSION['auth'] == 'operator')
				    $modbus->writeMultipleCoils($unitId, 0, [0]);
				exit();
			case "manualStart":
                if(isset($_SESSION['auth']) && $_SESSION['auth'] == 'operator')
				    $modbus->writeMultipleCoils($unitId, 0, [1]);
				exit();
            case "auth":
                if(isset($_GET['pass']) && $_GET['pass'] === apache_getenv('OPERATOR_PWD'))
                {
                    $_SESSION['auth'] = 'operator';
                    print "ok";
                }
                exit();
            case "deauth":
                // remove all session variables
                session_unset();
                // destroy the session
                session_destroy();
                exit();
		}
	}
?>
<html>
<head>
	<title>COGEDCT - Devoteam Cyber Trust</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script>
		var coils = new Array();
		var registers = new Array();
		var broken = false;

        var auth = "anonymous";
        <?php
            if(isset($_SESSION['auth']) && $_SESSION['auth'] == 'operator')
                echo "auth = \"operator\";";
        ?>

		function updateCoils()
		{
			var div_manual = document.getElementById('div_status_manual');
			var button_stop = document.getElementById('button_stop');
			var div_auto = document.getElementById('div_status_auto');
			var button_start = document.getElementById('button_start');
			if(coils[0] == false)
			{
				div_manual.style.backgroundColor = "red";
				button_stop.disabled = true;
                if(auth == "operator")
				    button_start.disabled = false;
			}
			if(coils[0] == true)
			{
				div_manual.style.backgroundColor = "green";
                if(auth == "operator")
				    button_stop.disabled = false;
				button_start.disabled = true;
			}
			if(coils[1] == false)
				div_auto.style.backgroundColor = "red";
			if(coils[1] == true)
				div_auto.style.backgroundColor = "green";
		}

		function updateRegisters()
		{
			document.getElementById('input_wind_speed').value = registers[0] + " m/s";
			document.getElementById('input_power_production').value = registers[1] + " kW";
		}

		function updateBroken()
		{
			if(broken)
				document.getElementById('img_eolienne').src = "eolienne_broken.png";
			else
				document.getElementById('img_eolienne').src = "eolienne.gif";
			
		}

		function manualStop()
		{
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function()
			{
				if(this.readyState == XMLHttpRequest.DONE)
				{
					updateCoils();
					updateRegisters();
				}
			}
			xhr.open('GET', 'index.php?act=manualStop', true);
			xhr.send(null);
		}

		function manualStart()
		{
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function()
			{
				if(this.readyState == XMLHttpRequest.DONE)
				{
					updateCoils();
					updateRegisters();
				}
			}
			xhr.open('GET', 'index.php?act=manualStart', true);
			xhr.send(null);
		}

        function authent()
        {
            var password = prompt("Mot de passe");
            if(password == null || password == "")
                return;

			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function()
			{
				if(this.readyState == XMLHttpRequest.DONE)
                    if(this.responseText == "ok")
                        location.reload();
                    else
                        alert("Mauvais mot de passe");
			}
			xhr.open('GET', 'index.php?act=auth&pass='+password, true);
			xhr.send(null);
        }

        function deauthent()
        {
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function()
			{
				if(this.readyState == XMLHttpRequest.DONE)
                    location.reload();
			}
			xhr.open('GET', 'index.php?act=deauth', true);
			xhr.send(null);
        }

		function updateData()
		{
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function()
			{
				if(this.readyState == XMLHttpRequest.DONE)
				{
					var results = JSON.parse(this.responseText);
					coils = results['coils'];
					registers = results['registers'];
					broken = results['broken'][0]
					updateCoils();
					updateRegisters();
					updateBroken();
				}
			}
			xhr.open('GET', 'index.php?act=readAll', true);
			xhr.send(null);
		}

		setInterval(updateData, 500);
	</script>
</head>
<body>
<center><h1 class="m-3">COGEDCT - Devoteam Cyber Trust</h1></center>
<center><img id="img_devoteam" src="devoteam_rgb_cybertrust.png" class="rounded m-3 d-block" style="padding: 1em"></center>
<center>
<table style="border-collapse: collapse;" width="80%">
<tr>
<td width="10%" style="border: 1px solid black;" valign="top">
<center><b>[ STATUS ]</b></center><br />
<div id="div_status_manual" style="border-radius: 5px; border: 1px solid black; display: inline;background-color: red;" width="25px">&nbsp; &nbsp; &nbsp; &nbsp;</div> Manual<br /><br />
<div id="div_status_auto" style="border-radius: 5px; border: 1px solid black; display: inline; background-color: red;" width="25px">&nbsp; &nbsp; &nbsp; &nbsp;</div> Automatic<br /> <br />

<center><b>[ ACTIONS ]</b></center><br />
<center>
<input style="text-align: center" onclick="manualStart()" type="button" id="button_start" value="START" disabled><br />
<input style="text-align: center" onclick="manualStop()" type="button" id="button_stop" value="STOP" disabled>
<br /><br /><br />
<?php
if(isset($_SESSION['auth']) && $_SESSION['auth'] == 'operator')
{
?>
<input style="text-align: center" onclick="deauthent()" type="button" id="button_deauth" value="Déconnexion">
<?php
}
else
{
?>
<input style="text-align: center" onclick="authent()" type="button" id="button_auth" value="Connexion opérateur">
<?php
}
?>
</center>
</td>
<td width="5%" style="text-align: center; border: 1px solid black" valign="top">
Vitesse du vent <input style="text-align: center; height: 50px; font-size: 2em" id="input_wind_speed" value="0 m/s" readonly>
</td>
<td width="80%" style="border: 1px solid black;">
<center><img id="img_eolienne" src="eolienne.gif"></center>
</td>
<td width="5%" style="border: 1px solid black; text-align: center" valign="bottom">
Production d'électricité (instantanée) <input style="text-align: center; height: 50px; font-size: 2em" id="input_power_production" value="0 kW" readonly>
</td>
</tr>
</table>
</center>
<div class="alert alert-success m-5" role="alert">
	<h4 class="alert-heading">Bienvenue au COGEDCT !</h4>
	<p>Mais qu'est-ce que le COGEDCT ? Il s'agit du centre opérationnel de gestion éléctrique de Devoteam Cyber Trust ! C'est ici que nous gerons l'alimentation éléctrique de nos datacenters.
	<p>Nous avons reçu un courrier anonyme expliquant que notre système était corronpu et que nous devions nous méfier de notre protocole MODBUS, il se pourrait qu'une personne malveillante pourrait détruire notre éolienne de production.</p>
	<p>Nous avons besoin de votre aide !!!</p>
	<hr>
	<p class="mb-0">Pour nous aider, il suffit de te connecter sur le réseau Wi-Fi suivant "nomWifiàDéfinir" et de te rendre sur ton navigateur préféré pour te rendre sur l'URL suivante : https://console.cogedct.local</p>
</div>
</body>
</html>
