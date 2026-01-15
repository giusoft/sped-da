// check for microphone/camera support!


var noop = function () {};
var MediaDevices = [];
var podeSom = false;
var escreveHora = false;
var ping;
var lastPingRefresh = '';
var apiCarregada = false;
var timerPodeAtivarPV;
var timerVerificaStatusAlunos;
var isHTTPs = location.protocol === 'https:';
var startUpRealizado = false;
var webcamAutorizada = false;
var canEnumerate = false;
var hasMicrophone = false;
var hasSpeakers = false;
var hasWebcam = false;
var isMicrophoneAlreadyCaptured = false;
var isWebcamAlreadyCaptured = false;
var naoAtualizouAtividades = true;
var alive = 0;
var usrClient = 0;
var usrInstrutorTeorico = 0;
var usrName;
var usrEmail;
var usrAvatar;
var usrGiuSoft = 0;
var usrAdmCfc = 0;
var canvas;
var gId = 0;
var api;
var bd='';
var obs = '';
var span;
var modal;
var video;
var title;
var displaySize;
var expression;
var fotoEntrada = false;
var fotoMeio = false;
var iniciouPV = false;
var fila='';
var filaBiometria = '';
var cronometro = 0;
var cronometroIniciadoEm = 0;
var cronometroMaximo = 0;
var cronometroAtual = 0;
var timerCronometro;
var timerHora;
var page = '';
var horaAtualCompleta = '';
var horaFinalDaAula = '';
var jaTocouAlarme = false;
var speedMbps = 0;
var fazerTesteDeInternet = true;
var internetMinima = 1;
var internetMedia = 4;
var orientacao = 'Paisagem';
var localhost = false;
var aulaIniciada = false;
var intervalFaceAPI;
var room = '';
var server = 'meet.jit.si';
var biometriaReconhecida = false;
var fotoProcessada = false;
var jitsiIniciado = false;
var token = '';
var horariosDeFotos = [];
var fotosColetadas = [];
var caminhoFotos = '';
const expressions = ['neutral', 'happy','surprised'];
const etranslated = ['EXPRESSÃO NEUTRA', 'SORRIA!','DEMONSTRE SURPRESA...'];

var alarme = new Audio('gfw/mp3/alarme.mp3');

async function sleep(milliseconds) {
	const date = Date.now();
	let currentDate = null;
	do {
	  currentDate = Date.now();
	} while (currentDate - date < milliseconds);
}

function detectWebGL()
{
    // Check for the WebGL rendering context
    if ( !! window.WebGLRenderingContext) {
        var canvas = document.createElement("canvas"),
            names = ["webgl", "experimental-webgl", "moz-webgl", "webkit-3d"],
            context = false;

        for (var i in names) {
            try {
                context = canvas.getContext(names[i]);
                if (context && typeof context.getParameter === "function") {
                    // WebGL is enabled.
					log("==> WEBGL habilitado!");
                    return 1;
                }
            } catch (e) {}
        }
		log("==> WEBGL suportado, porém desabilitado!");
        // WebGL is supported, but disabled.
        return 0;
    }

    // WebGL not supported.
	log("==> WEBGL não suportado!");
    return -1;
};

if (typeof MediaStreamTrack !== 'undefined' && 'getSources' in MediaStreamTrack) {
	canEnumerate = true;
} else if (navigator.mediaDevices && !!navigator.mediaDevices.enumerateDevices) {
	canEnumerate = true;
}

// Some browsers partially implement mediaDevices. We can't just assign an object
// with getUserMedia as it would overwrite existing properties.
// Here, we will just add the getUserMedia property if it's missing.
if (navigator.mediaDevices.getUserMedia === undefined) {
	navigator.mediaDevices.getUserMedia = function(constraints) {

		// First get ahold of the legacy getUserMedia, if present
		var getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

		// Some browsers just don't implement it - return a rejected promise with an error
		// to keep a consistent interface
		if (!getUserMedia) {
		return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
		}

		// Otherwise, wrap the call to the old navigator.getUserMedia with a Promise
		return new Promise(function(resolve, reject) {
		getUserMedia.call(navigator, constraints, resolve, reject);
		});
	}
}


//navigator.getUserMedia = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);

function requestPermissions() {
	log('Solicitou permissões...');
	navigator.mediaDevices.getUserMedia({audio: true, video: true},
	function(){
		// Autorizado
		log('Autorizou a webcam!');
		webcamAutorizada = true;
		executaFila();
	},
	function() {
		// Não autorizado
		log('Não autorizou a webcam!');
		webcamAutorizada = false;
		verificaSePossuiPermissoes(callBackPermissoes);
	}).then(function(){
		log('Autorizou a webcam!');
		webcamAutorizada = true;

		executaFila();
	}).catch(function(err){
		log('Não autorizou a webcam!');
		webcamAutorizada = false;
		verificaSePossuiPermissoes(callBackPermissoes);
	});
  }


// function requestPermissions() {
// 	navigator.getUserMedia({audio: true, video: true}, noop, noop)
// }

if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
	// Firefox 38+ seems having support of enumerateDevicesx
	navigator.enumerateDevices = function(callback) {
		navigator.mediaDevices.enumerateDevices().then(callback);
	};
}

function verificaSePossuiPermissoes(callback) {
	log("Verificando permissões...");
	if (!canEnumerate) {
		return;
	}

	if (!navigator.enumerateDevices && window.MediaStreamTrack && window.MediaStreamTrack.getSources) {
		navigator.enumerateDevices = window.MediaStreamTrack.getSources.bind(window.MediaStreamTrack);
	}

	if (!navigator.enumerateDevices && navigator.enumerateDevices) {
		navigator.enumerateDevices = navigator.enumerateDevices.bind(navigator);
	}

	if (!navigator.enumerateDevices) {
		if (callback) {
			callback();
		}
		return;
	}

	MediaDevices = [];
	navigator.enumerateDevices(function(devices) {
		devices.forEach(function(_device) {
			var device = {};
			for (var d in _device) {
				device[d] = _device[d];
			}

			if (device.kind === 'audio') {
				device.kind = 'audioinput';
			}

			if (device.kind === 'video') {
				device.kind = 'videoinput';
			}

			var skip;
			MediaDevices.forEach(function(d) {
				if (d.id === device.id && d.kind === device.kind) {
					skip = true;
				}
			});

			if (skip) {
				return;
			}

			if (!device.deviceId) {
				device.deviceId = device.id;
			}

			if (!device.id) {
				device.id = device.deviceId;
			}

			if (!device.label) {
				device.label = 'Please invoke getUserMedia once.';
				if (!isHTTPs) {
					device.label = 'HTTPs is required to get label of this ' + device.kind + ' device.';
				}
			} else {
				if (device.kind === 'videoinput' && !isWebcamAlreadyCaptured) {
					isWebcamAlreadyCaptured = true;
				}

				if (device.kind === 'audioinput' && !isMicrophoneAlreadyCaptured) {
					isMicrophoneAlreadyCaptured = true;
				}
			}

			if (device.kind === 'audioinput') {
				hasMicrophone = true;
			}

			if (device.kind === 'audiooutput') {
				hasSpeakers = true;
			}

			if (device.kind === 'videoinput') {
				hasWebcam = true;
			}

			// there is no 'videoouput' in the spec.

			MediaDevices.push(device);
		});
		log("Verificação de permissões concluída.");
		if (callback) {
			callback();
		}
	});
}


function callBackPermissoes()
{
	log("Processando callback de permissões...");
	if (!hasWebcam)
	{
		bootbox.alert("Você precisa de uma webcam ou câmera do celular para acessar a aula!");
		$('#podeEntrarSim').hide();
		$('#podeEntrarNao').show();
	} else {
		if (!isMicrophoneAlreadyCaptured)
		{
			log("Permissões: Microfone - Negado!");
			//bootbox.alert("Você precisa autorizar o uso do microfone para acessar a aula!");
		}
		if (!isWebcamAlreadyCaptured)
		{
			log("Permissões: Camera - Negada!");
			$('#podeEntrarSim').hide();
			$('#podeEntrarNao').show();
			dialog = bootbox.dialog({
				title: 'ATENÇÃO!',
				message:'Você precisa autorizar o uso da câmera pelo navegador para poder acessar a aula!',
				closeButton: false,
				backdrop:false,
				animate:true,
				size: 'xl',
				buttons:{confirm: {
					label: 'Entendi e vou fazer agora',
					callback: function (result) {
						requestPermissions()
						return true;
					}
				}},
				onShown: function(e){
				}
			});
		} else {
			log("Permissões: Camera - Aprovada!");
			webcamAutorizada = true;
		}
	}
}


//JUST AN EXAMPLE, PLEASE USE YOUR OWN PICTURE!
var imageAddr = "../../pub/img/test.jpg";
//var downloadSize = 5474563; //bytes
var downloadSize = 168018;

function ShowProgressMessage(msg) {
    // if (console) {
    //     if (typeof msg == "string") {
    //         console.log(msg);
    //     } else {
    //         for (var i = 0; i < msg.length; i++) {
    //             console.log(msg[i]);
    //         }
    //     }
    // }

    var oProgress = document.getElementById("internetSpeed");
    if (oProgress) {
        var actualHTML = (typeof msg == "string") ? msg : msg.join("<br />");
        oProgress.innerHTML = actualHTML;
    }
}

function InitiateSpeedDetection() {
	if (fazerTesteDeInternet)
	{
		log("Teste de Internet ativado!");
		ShowProgressMessage('<i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Testando...');
		setTimeout(MeasureConnectionSpeed, 1);
	} else {
		log("Teste de Internet desativado!");
		testeLiberado();
	}
};

function liberacaoFinal(tipo)
{
	$('#testandoInternet').show();
	switch (tipo)
	{
		case 0:
			$('#trInternetSpeed').removeClass('info');
			$('#trInternetSpeed').addClass('danger');
			$('#podeEntrarSim').hide();
			$('#podeEntrarNao').show();
			break;
		case 1:
			$('#trInternetSpeed').removeClass('info');
			$('#trInternetSpeed').addClass('success');
			$('#mensagemAdicional').html('<div class="alert alert-warning" role="alert">Podem ocorrer problemas na sua aula por conta da velocidade da Internet...</div>');
			break;
		case 2:
			$('#trInternetSpeed').removeClass('info');
			$('#trInternetSpeed').addClass('success');
			break;
		case 3:
			$('#trInternetSpeed').removeClass('info');
			$('#trInternetSpeed').addClass('success');
			break;

	}
}

function enviarObs(obs, tipoLiberacao)
{
	switch(tipoLiberacao)
	{
		case 0:
			speedMsg = speedMbps + " - Fraca!";
			break;
		case 1:
			speedMsg = speedMbps + " - Razoável!";
			break;
		case 2:
			speedMsg = speedMbps + " - Boa!";
			break;
		case 3:
			speedMsg = "Não foi testada";
			break;
	}
	ShowProgressMessage( speedMsg );
	log("Enviando obs...");
	obs=obs + 'Velocidade aproximada de Internet~' + speedMsg + "_";
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=internet&obs='+obs,
		context: document.body
	})
	.done(function(data,textStatus){
		liberacaoFinal(tipoLiberacao);
	});
}

function testeLiberado()
{
	enviarObs(obs, 3);
}

function MeasureConnectionSpeed() {
    var startTime, endTime;
    var download = new Image();
    download.onload = function () {
        endTime = (new Date()).getTime();
        showResults();
    }

    download.onerror = function (err, msg) {
        ShowProgressMessage("Erro no teste");
    }

    startTime = (new Date()).getTime();
    var cacheBuster = "?nnn=" + startTime;
    download.src = imageAddr + cacheBuster;

    function showResults() {
        var duration = (endTime - startTime) / 1000;
        var bitsLoaded = downloadSize * 8;
        var speedBps = (bitsLoaded / duration).toFixed(2);
        var speedKbps = (speedBps / 1024).toFixed(2);
		speedMbps = (speedKbps / 1024).toFixed(2);
		var speedMsg = '';
		if (speedMbps < internetMinima)
		{
			tipoLiberacao = 0;
		} else if (speedMbps < internetMedia ) {
			tipoLiberacao = 1;
		} else {
			tipoLiberacao = 2;
		}
		enviarObs(obs, tipoLiberacao);
    }
}


async function log(msg)
{
	console.log(msg);
	if (localhost)
	{
		atualizaHoraAtual();
		await $('#log').html($('#log').html()+'<br>'+horaAtualCompleta+' » '+msg);
	}
}




function mostrarBiometria()
{
	log("Mostrar biometria...");
	alive = 0;
	$('#video').show();
	$('#title').show();
	if (orientacao=="Paisagem")
	{
		$('#mask0').show();
		$('#mask0').css({ 'display' : '' });
	} else {
		$('#mask1').show();
		$('#mask1').css({ 'display' : '' });
	}
	startVideo();
}

function ocultarBiometria()
{
	hideExamples()
	$('#mask0').hide();
	$('#mask1').hide();
	$('#video').hide();
}

function coletarBiometria()
{
	$('#screenshot').hide();
	if (apiCarregada)
	{
		log('Coletando biometria...');

		$('#btnTentarNovamente').hide();
		$('#erroProvaDeVida').hide();
		if (webcamAutorizada)
		{

			alive = 0;
			mostrarBiometria();

			if (usarPV)
			{
				iniciarCronometro(45);
			} else {
				iniciarCronometro(5);
			}
		} else {
			log('Webcam não autorizada... agendando evento na fila.');
			fila = 'coletarBiometria';
		}
	} else {
		log("API não carregada... esperando um pouco...");
		setTimeout(coletarBiometria,3000);
	}
}



function executaFila()
{
	log('Executou fila...');
	if (fila=='coletarBiometria')
	{
		coletarBiometria()
	}
}

function atualizaHoraAtual()
{
	var data = new Date();
	var dia     = data.getDay();
	var mes     = data.getMonth();
	var ano     = data.getYear();	
	var hora    = data.getHours();
	var min     = data.getMinutes();
	var seg     = data.getSeconds();
	
	if (dia<10)
	{
		dia = '0'+dia;
	}
	if (mes<10)
	{
		mes = '0'+mes;
	}
	if (hora<10)
	{
		hora = '0'+hora;
	}
	if (min<10)
	{
		min = '0'+min;
	}
	if (seg<10)
	{
		seg = '0'+seg;
	}
	horaAtual = hora + ':' + min ;
	horaAtualCompleta = dia+'/'+mes+'/'+ano+' '+horaAtual + ':' + seg;
}

function atualizaCronometro()
{
	if (cronometroMaximo>0)
	{
		var data = new Date();
		var hora    = data.getHours();
		var min     = data.getMinutes();
		var seg     = data.getSeconds();
		var tempoAtual = hora*60*60+min*60+seg;
		cronometroAtual = tempoAtual - cronometroIniciadoEm;
		cronometroRegressivo = cronometroMaximo - cronometroAtual;
		if (cronometroRegressivo <= 0)
		{
			finalizarCronometro();
		}
	}
}

function iniciarCronometro(tempo)
{
	var data = new Date();
	var hora    = data.getHours();
	var min     = data.getMinutes();
	var seg     = data.getSeconds();
	cronometroIniciadoEm  = hora*60*60+min*60+seg;
	cronometroMaximo = tempo;
	cronometroAtual = 0;
	timerCronometro = setInterval(atualizaCronometro, 1000);
}

function cancelarCronometro()
{
	log("Cancelou cronometro");
	clearInterval(timerCronometro);
	clearInterval(intervalFaceAPI);
	cronometroMaximo = 0;
	cronometroAtual = 0;
	ocultarBiometria()
}

function finalizarCronometro()
{
	log("Finalizou cronômetro");
	cancelarCronometro()
	if (usarPV)
	{
		log("PV Ativado. Prazo expirou...");
		mensagemErroProvaDeVida()
	} else {
		log("PV Desativado. Tirando foto...");
		tiraFoto();
	}
}

function mensagemOk(score)
{
	log("Ok! ");
	$('#mensagemOk').fadeIn("fast", function(){
		setTimeout(function(score){
			//$('#title').html('');
			callBackOk(score);
		},3000);
	});
}

function mensagemErroProvaDeVida()
{
	log("Prova de vida falhou... ");
	$('#title').html('');
	setTimeout(function(){
		$('#erroProvaDeVida').fadeIn("slow", function(){
			setTimeout(function(){
				console.log("mostrando botão de tentar novamente...");
			$('#btnTentarNovamente').fadeIn("slow");
			},2000);
		});
	}, 2000);

}

function mensagemErroForaHorario()
{
	log("Biometria falhou (fora do horário)... ");
	$('#title').html('');
	setTimeout(function(){
		$('#erroForaHorario').fadeIn("fast", function(){
			setTimeout(function(){
			$('#btnErroSair').fadeIn("fast");
			},2000);
		});
	}, 2000);

}

function mensagemErroNaoFoiPossivel()
{
	log("Biometria falhou (não foi possível foto agora)... ");
	$('#title').html('');
	setTimeout(function(){
		$('#erroNaoFoiPossivel').fadeIn("fast", function(){
			setTimeout(function(){
			$('#btnErroSairFora').fadeIn("fast");
			},2000);
		});
	}, 2000);

}


function sendRequest(url,callback,postData) {
    var req = createXMLHTTPObject();
	if (!req) return;
	var method = (postData) ? "POST" : "GET";
	req.open(method,url,true);
    if (postData)
        req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    req.onreadystatechange = function () {
		if (req.readyState != 4) return;
        if (req.status != 200 && req.status != 304) {
            return;
		}
        callback(req);
	}
    if (req.readyState == 4) return;
    req.send(postData);
}

var XMLHttpFactories = [
    function () {return new XMLHttpRequest()},
    function () {return new ActiveXObject("Msxml2.XMLHTTP")},
    function () {return new ActiveXObject("Msxml3.XMLHTTP")},
    function () {return new ActiveXObject("Microsoft.XMLHTTP")}
];

function createXMLHTTPObject() {
    var xmlhttp = false;
    for (var i=0;i<XMLHttpFactories.length;i++) {
        try {
            xmlhttp = XMLHttpFactories[i]();
        }
        catch (e) {
            continue;
        }
        break;
    }
    return xmlhttp;
}


function obtemC()
{
	if (server=='e-mil.loggo.com.br')
	{
		sendRequest('https://ide.giusoft.com.br/m.php',function(req){
			log("obtemC: "+req.response);
			usrPing(req.response);
		},'');
	} else {
	}
}

function atualizar()
{
	
	
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=atualizar',
		context: document.body
	}).done(function (data) {
		//console.log(data);
		let json = JSON.parse(data);
		server = json.servidor_jitsi;
		room = json.link;
		usrClient = 0;
		token = json.jwt;
		
		

	}).fail(function (err) {
		
		console.error(err);
	});
}

function mostrarAcesso(id)
{
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=mostrar_acesso&id='+id+'&gId='+gId+'&bd='+bd,
		context: document.body
	})
	.done(function(data,textStatus){
		bootbox.dialog({
			message: data,
			backdrop: true,
			centerVertical: true,
		});
	})
	.error(function(){
		bootbox.alert("Houve um erro na comunicação. Tente novamente daqui a pouco...");
	});


}

function aplicarAtividade(id)
{
	log("Aplicando atividades...");
	$('#aplicada'+id).html('<i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Aguarde...');

	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=aplicar_atividade&id='+id,
		context: document.body
	})
	.done(function(data,textStatus){
		$('.aplicada').html('');
		$('#btnAtividade_'+id).attr("disabled","disabled");
		$('#aplicada'+id).html('<span class="label label-danger">Aplicada</span>');
	})
	.error(function(){
		$('.aplicada').html('');
		$('#aplicada'+id).html('<span class="label label-danger">Erro! Tente novamente...</span>');
	});
}


function responderPergunta(id_atividade, id_questao,opcao)
{
	log("Respondendo pergunta...");
	questao = $('#questao').html();
	$('#questao').html('<div style="width: 100%; height: auto; text-align: center; vertical-align: middle"><i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Aguarde...</div>');
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=responder&id_atividade='+id_atividade+'&id_questao='+id_questao+'&opcao='+opcao,
		context: document.body
	})
	.done(function(data,textStatus){
		$('#questao').html(data);
	})
	.error(function(){
		$('#questao').html(questao);
	});
}

function atualizaAtividades()
{
	$('#atividades').html('<span style="color: white"><i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Carregando informações...</span><br>');
	log("Atualizando atividades...");
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=atividades&gId='+gId+'&bd='+bd,
		context: document.body
	})
	.done(function(data,textStatus){
		$('#atividades').html(data);
	})
	.error(function(){
		$('#questao').html('');
		mostraAtividades()
	});

}

function mostraAtividades()
{
	$('#alunos').hide();
	if ($('#atividades').is(":hidden"))
	{
		$('#atividades').fadeIn("slow");
		atualizaAtividades();
		// if (naoAtualizouAtividades)
		// {
		// 	naoAtualizouAtividades = false;
		// 	atualizaAtividades();
		// }
	} else {
		$('#atividades').fadeOut("slow");
	}
}

function autorizaAtualizaAtividades()
{
	dialog = bootbox.dialog({
		title: 'Recomeçar atividade',
		message: 'Se você confirmar esta operação e já tiver alguma atividade em andamento, ela será descartada e você voltará ao início.',
		closeButton: false,
		backdrop:false,
		animate:true,
		size: 'xl',
		buttons:{confirm:
			{
				label: 'Confirmar',
				className: 'btn-success',
				callback: function (result) {
					atualizaAtividades()
					return true;
				}
			},
			cancel: {
				label: 'Cancelar',
				className: 'btn-warning'
			}
		},
		onShown: function(e){
		}
	});
}

function atualizaAlunos()
{
	$('#alunos').html('<span style="color: white"><i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i> Carregando informações...</span><br>');
	log("Atualizando alunos...");
	$.ajax({
		url: 'teleaula_pv_ajax.php?cmd=alunos&gId='+gId+'&bd='+bd,
		context: document.body
	})
	.done(function(data,textStatus){
		$('#alunos').html(data);
	})
	.error(function(){
		$('#alunos').html('');
		mostraAlunos();
	});

}

function mostraAlunos()
{
	setTimeout(function(){
		$('#atividades').hide();
		if ($('#alunos').is(":hidden"))
		{
			$('#alunos').fadeIn("slow");
			atualizaAlunos();
		} else {
			$('#alunos').fadeOut("slow");
		}

	},500);
}

function hide(elements) {
	elements = elements.length ? elements : [elements];
	for (var index = 0; index < elements.length; index++) {
	  elements[index].style.display = 'none';
	}
  }



// Verificações para o instrutor: dados dos alunos, etc.
function verificaStatusAlunos()
{

}


// PV

function hideExamples()
{
	$('#pv0').hide();
	$('#pv1').hide();
	$('#pv2').hide();
}



if (usarPV)
{
	log('Carregando API PV...');
	Promise.all([
		faceapi.nets.tinyFaceDetector.loadFromUri('gfw/inc/lib/face-api/models'),
		faceapi.nets.faceLandmark68Net.loadFromUri('gfw/inc/lib/face-api/models'),
		faceapi.nets.faceExpressionNet.loadFromUri('gfw/inc/lib/face-api/models'),
		faceapi.nets.faceRecognitionNet.loadFromUri('gfw/inc/lib/face-api/models'),
	])
	.then(function() {log("API Carregada!");apiCarregada = true;});
} else {
	log('API PV desativada...');
	apiCarregada = true;
}

function startVideo() {
	log("startVideo");
	if (usrGiuSoft == 1) {
		log("Iniciando webcam (Suporte)");
		// Somente para o perfil Detran e Suporte
		const queryParams = new URLSearchParams(window.location.search);
		gId = queryParams.get('gId');
		bd = queryParams.get('bd');

		$.ajax({
			url: 'teleaula_pv_ajax.php?cmd=entraSuporte&gId=' + gId + '&bd=' + bd,
			context: document.body
		}).done(function (data) {
			// console.log(data);
			let json = JSON.parse(data);
			server = json.servidor_jitsi;
			room = json.link;
			usrClient = 0;
			token = json.jwt;
			
		}).fail(function (err) {
			console.error(err);
		});
	} else if(usrAdmCfc == 1) {
		$('#horaFinal').val(horaFinalDaAula);
		
	} else
	{
		// Perfil do instrutor ou aluno
		log("Iniciando webcam (Instrutor ou Aluno)");
		settingExpression();
		if(navigator.mediaDevices.getUserMedia) {
			navigator.mediaDevices.getUserMedia({video: true}).then(setStream);
		}
	}
}

async function podeAtivarPV()
{
	if (video.readyState>=3)
	{
		clearInterval(timerPodeAtivarPV);
		log("Criando Canvas...");
		if (usarPV)
		{
			canvas = await faceapi.createCanvasFromMedia(video);
			el = document.getElementById("faceDetectionCanvas");
			el.append(canvas);
			faceapi.matchDimensions(canvas, displaySize);
			handleDetections(canvas);	
		} else {
		}
	}

}



const setStream = function (stream) {
	log("Ativando webcam...");
	video.srcObject = stream;
	timerPodeAtivarPV = window.setInterval(podeAtivarPV,750);
}



async function handleDetections (canvas) {
	continua = true;

	intervalFaceAPI = window.setInterval(async () => {
		console.log("Alive nº: "+alive);
		if(alive <= maxPV)
		{
			const detections = await faceapi.detectSingleFace(
				video,
				new faceapi.TinyFaceDetectorOptions({inputSize: 128}) // 128, 160, 224, 320, 416, 512, 608
				).withFaceExpressions();
			if (detections !== undefined)
			{
				//console.log(detections);
				await handleResults(
					detections.expressions,
					detections.detection.score
					);
			} else {
				console.log("Nenhuma face encontrada... Nº"+alive);
				// alive = 999;
				// clearInterval(intervalFaceAPI);
				// finalizarCronometro();
			}
		} else {
			clearInterval(intervalFaceAPI);
			if (alive<999)
			{
				cancelarCronometro();
				await tiraFoto();
			} else {
				finalizarCronometro();
				log("Terminou...");
			}
		}
	}, 750);

}

async function handleResults (results, score) {
	//log(eselected+': '+results[eselected]+' ('+score+') -- neutral: '+results['neutral']+' -- happy: '+results['happy']+' -- surprise: '+results['surprised'])
	if((Number(results[eselected]) > 0.8) && (score > 0.5)) {
		alive++;
		settingExpression();
		await handleSuccessExpression(eselected);
	}  else {
		if
		(
			(eselected!='neutral') &&
			(
				((Number(results['happy']) > 0.8) && (score > 0.5)) ||
				((Number(results['surprised']) > 0.8) && (score > 0.5))
			)
		)
		{
			alive = 999;
			clearInterval(intervalFaceAPI);
			cancelarCronometro();
			ocultarBiometria();
			$('#enviandoImagem').fadeOut("fast");
			mensagemErroProvaDeVida();
		}
	}
}


async function handleSuccessExpression (eselected) {
	log('Expressão reconhecida: '+eselected);
	clearInterval(this);
	clearTimeout(this);
}

function settingExpression () {
	var r;
	if (alive >= maxPV) return
	if (alive / 2 == parseInt(alive/2))
	{
		r = 0;
	} else {
		r = customRandomRange(1, 3)
	}
	eselected = expressions[r];
	hideExamples();
	$('#pv'+r).show();
	//modal.style.display = "block";
	$('#title').html(`${etranslated[expressions.indexOf(eselected)]}`);
	//title.innerHTML = ;
	//expression.innerHTML = `${etranslated[expressions.indexOf(eselected)]} ${alive}`;
	log(`Detectar expressão: ${etranslated[expressions.indexOf(eselected)]}`);
}


function customRandomRange(min, max) {
	min = Math.ceil(min);
	max = Math.floor(max);
	return Math.floor(Math.random() * (max - min)) + min;
}

async function tiraFoto()
{
	fila = '';
	hideExamples();

	cancelarCronometro()

	var canvas2 = document.createElement('canvas');

	var width = video.videoWidth;
	var height = video.videoHeight;

	canvas2.width = width;
	canvas2.height = height;

	atualizaHoraAtual();
	context = canvas2.getContext('2d');
	context.drawImage(video, 0, 0, width, height);
	if (escreveHora)
	{
		context.font = "15pt Courier";
		context.fillStyle = "#666666";
		context.fillText(horaAtualCompleta, 26, 26);	
		context.fillStyle = "#ffffff";
		context.fillText(horaAtualCompleta, 24, 24);	
	}
	let data_uri = canvas2.toDataURL('image/jpeg',qualidadeJpeg);

	document.querySelector('#screenshot').src = data_uri;
	await video.pause();
	$('#screenshot').show();
	$('#enviandoImagem').show();
	title.innerHTML = "Aguarde...";
	await $.post("?cmd=saveImage",{data_uri : data_uri}).then( data =>{
		score = parseInt(data);
		console.log("*** Ok");
		if(score>=60)
		{
			clearInterval(intervalFaceAPI);
			title.innerHTML = "Usuário confirmado";
			mensagemOk(data);
			// $('#enviandoImagem').fadeOut("fast", function(){				
			// 	mensagemOk(data);
			// });
		} else{
			title.innerHTML = "";
			$('#enviandoImagem').fadeOut("fast");
			mensagemErroProvaDeVida();
		}
	})
	.fail(function(xhr, status, error){
		console.log("*** Falha!");
		var errorMessage = xhr.status + ': ' + xhr.statusText
		log("Erro ao enviar foto...");
		title.innerHTML = "";
		$('#enviandoImagem').fadeOut("fast");
		mensagemErroProvaDeVida();
	});
}

async function handleShowPicture(canvas, interval) {
	//await canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

	// const track = video.srcObject.getVideoTracks()[0]
	// const imageCapture = new ImageCapture(track);
	// const blob = await imageCapture.takePhoto();
	// var urlCreator = window.URL || window.webkitURL;
	// var imageUrl = urlCreator.createObjectURL(blob);
	// document.querySelector('#screenshot').src = imageUrl;

	tiraFoto();

}

function mostrarImagem(id)
{
	var img = caminhoFotos+'.'+id+'.jpg';
	//console.log(img);
	bootbox.dialog({
		message: '<img class="img img-rounded" style="width: 120px;height: auto" src="'+img+'">',
		size: 'small',
		backdrop: true,
		centerVertical: true,
	});
}

function startUp()
{
	if (!startUpRealizado)
	{
		startUpRealizado = true;
		log("-- Startup --");
		$(function () { $('[data-toggle="tooltip"]').tooltip() })
		span = document.getElementsByClassName("close")[0];
		modal = document.getElementById("myModal");
		video = document.getElementById("video");
		// window.setInterval(function(){
		// 	log(video.readyState);
		// },500);
		title = document.getElementById("title");
		displaySize = { width: video.width, height: video.height };
		expression = document.getElementById("expression");

		// No Chrome funciona a linha abaixo, no Safari, trava a webcam
		// video.addEventListener('play', startFaceAPI);
	}
}

$("document").ready(function(){
	if (usarPV)
	{
		const temWebgl = detectWebGL();
		if (temWebgl!=1)
		{
			usarPV = false;
		}	
	}

	podeSom = true;
	$.ajaxSetup({
		timeout: 60000
	});
	startUp();
});

