
//remove the scrollbars on the map
$('body').css('overflow', 'hidden');

//get the background colour - used in zen mode and for the map bg
var background_colour = $('body').css('background-color');

//setup some static data - time periods and system colours etc
var time_periods = [["year",31536000], ["month", 2592000], ["week", 604800], ["day", 86400], ["hour", 3600], ["minute", 60], ["second", 1]];
var system_colours = [0xF00000, 0xD73000, 0xF04800, 0xF06000, 0xD77700, 0xEFEF00, 0x8FEF2F, 0x00F000, 0x00EF47, 0x48F0C0, 0x2FEFEF];

//setup a whole boat load of globals to hold stuff
var systems = {}, ships = [], kill_history = [], kill_history_throttle, container, overlay, time = Date.now();;
var camera, cameraTarget, scene, renderer, particles, geometry, material, h, color, sprite, size, x, y, z, maxX = 0, maxY = 0, maxZ = 0, uniforms, attributes, lat = 0, lon = 0;

//kick the party off
init();
loadsystems();
animate();
	
function init() {

	//get the main element for our map to render in
	container = document.getElementById('killmap_render');

	//setup the three.js stuff
    scene    = new THREE.Scene(); 
	camera   = new THREE.PerspectiveCamera( 60, window.innerWidth / window.innerHeight, 1, 10000 );
	renderer = new THREE.WebGLRenderer( { antialias: true, clearColor: 0x000000, clearAlpha: 1} );
	controls = new THREE.TrackballControls( camera );
	
	//create the camara and add it to the scene
    camera.position.y = 600;
    camera.position.z = 100;
    camera.position.x = 0;

    scene.add( camera );

    //create the renderer and append the dom to the page
    renderer.setSize( window.innerWidth - 17, window.innerHeight );
	container.appendChild( renderer.domElement );

    //setup the control settings
    controls.rotateSpeed = 3.0;
    controls.zoomSpeed = 5;
    controls.panSpeed = 0.8;
    controls.noZoom = false;
    controls.noPan = false;
    controls.staticMoving = true;
    controls.dynamicDampingFactor = 0.3;
    controls.keys = [ 65, 83, 68 ];

	console.log(camera);

	//listen for the window resize event 
    window.addEventListener( 'resize', onWindowResize, false );

	//load the ship details
	$.getJSON('/js/starmap-ships.json', function(data) { ships = data; });	
	
	//setup the stomple connection - yay websockets = we should get this from the config setting
	var client = Stomple.create_client({
		url : "ws://stomp.zkillboard.com:61623/",
		destination : "/topic/starmap.systems.active",
		login : "guest",
		passcode : "guest"
	});

	//connect and handle any incoming messages
	client.subscribe({handler : function(msg) {
		data = JSON.parse(msg.body);

		//ping the system - blinkies!
		systems[data['solarSystemID']].blink();

		//add the item to the start of the stack - with the time we got it
		data.time = Date(); kill_history.unshift(data);

		//clear any existing killog renders
		clearTimeout(kill_history_throttle);

		//rander the killog 200ms after the last system in this batch is gotton
		kill_history_throttle = setTimeout(renderHistory, 200);			
	}});
	

	//setup zen mode
	$('#killmap_controls a[href="#zen"]').click(function(event){
		event.preventDefault();

		if (overlay === undefined) {
			//change the maps z-index - so its over the overlay
			$('#killmap_render').css('z-index', 2001);;
			
			//animate the menus and controls so they move upwards
			$('#killmap_history, #killmap_controls').animate({'top' : 10});

			//append and fade a background to hide clutter
			overlay = $('<div id="killmap_zenmode"></div>').css('background', background_colour).appendTo('body').fadeIn(300);
		}
		else
		{
			//return the settings back to normal
			$('#killmap_history, #killmap_controls').animate({'top' : 60});

			//append and fade a background to hide clutter
			$(overlay).fadeOut(300, function(){ $(overlay).remove(); $('#killmap_render').css('z-index', 'auto'); }); overlay = undefined;
		}
	});

	/*
	//ping all - temp blinky test
	$('#killmap_controls a[href="#reset"]').click(function(event){
		//flash 50 systems at random
		keys = Object.keys(systems);
    
		for (i = 0; i <= 50; i++) {	systems[keys[Math.floor(keys.length * Math.random())]].blink();	}

		systems[30004402].blink();
		systems[30001773].blink();
		systems[30004550].blink();
		systems[30003165].blink();
	});
	*/
}

function renderHistory() {
	var killlog_html = ''; 
	for (i = 0; i < Math.min(40, kill_history.length); i++) { 
		

		//get the relevent ship and system details
		ship_details = ships[kill_history[i].shipTypeID], system_details = systems[kill_history[i].solarSystemID];
		
		//bodge the html together
		killlog_html += '<li' + ((ship_details.bold === 1) ? ' class="bold"' : '') + '>' + ship_details.name + ' lost in ' + system_details.name + ', ' + timeago(kill_history[i].killTime) + '</li>'; 
	}
	
	//empty the history queue and display the new data
	$('#killmap_history').empty().append('<ul>' + killlog_html + '<ul>');	
}

function timeago(timestamp)
{
	//create a date/time object from the timestamp
	parts = timestamp.match(/(\d+)/g);	timestamp = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]); 

	//handle tie timezone madness
	currenttime = new Date(); currenttime.setHours(currenttime.getUTCHours());
	
	//get the difference between now and the timestamp in seconds - and set the string buffer
	var time_diff = ((currenttime.getTime() - timestamp.getTime()) / 1000), time_difference_string = '';

	//for each of our time groups start building the string
	for (var i in time_periods) {
		if (time_diff >= time_periods[i][1]) {
			//calculate how many of the current unit we have
			units_calculated = (time_diff - (time_diff % time_periods[i][1])) / time_periods[i][1];

			//calculate the reminder and set it to the time varaialbe for the next iteration
			time_diff = (time_diff % time_periods[i][1]);
			
			//build our string up - adding and ands and commas as needed
			time_difference_string += ', ' + units_calculated + ' ' + ((units_calculated > 1) ? time_periods[i][0] + 's' : time_periods[i][0]);
		}
	}

	//return the final string - trime the comman and add an and
	return time_difference_string.substr(2).trim().replace(/,(?=[a-z0-9 ]+$)/, ' and') + ' ago';
}

function onDocumentMouseDown( event ) {
    event.preventDefault();

    isUserInteracting = true;

    onPointerDownPointerX = event.clientX;
    onPointerDownPointerY = event.clientY;

    onPointerDownLon = lon;
    onPointerDownLat = lat;
}

function onDocumentMouseMove( event ) {
    if ( isUserInteracting ) {
        lon = ( onPointerDownPointerX - event.clientX ) * 0.1 + onPointerDownLon;
        lat = ( event.clientY - onPointerDownPointerY ) * 0.1 + onPointerDownLat;
    }
}

function onDocumentMouseUp( event ) {
    isUserInteracting = false;
}

function onWindowResize( event ) {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize( window.innerWidth, window.innerHeight );
}

function animate() {
    requestAnimationFrame( animate );
    if(particles){ render(); }
}

function render() {
    var nTime = Date.now();
    var change = (nTime - time) / 15000.0;
    time = nTime;

    for (var k in systems) {
        if (systems[k].lum > 0.25) {

            systems[k].lum -= change;

            if (systems[k].lum < 0.25) {
                systems[k].lum = 0.25; 
				attributes.ca.value[systems[k].index].setRGB(0.40, 0.40, 0.40);
				attributes.size.value[ systems[k].index ] = 10;
            }
            else
			{
                system_color = new THREE.Color(system_colours[systems[k].sec]);
				attributes.ca.value[systems[k].index].setRGB(system_color.r, system_color.g, system_color.b);
				attributes.size.value[ systems[k].index ] = 30 + ( systems[k].lum - 0.25) * ( (30 / 2) * Math.sin(time * 0.005 ) );
            }
        }

    }

	//perform a render pass - yay stars
    controls.update(); renderer.render( scene, camera );
}




function loadsystems() {
    $.getJSON('/js/starmap-systems.json', function(data) {
		geometry = new THREE.Geometry();

		attributes = { size: { type: 'f', value: [] }, ca: { type: 'c', value: [] } };
		uniforms = { amplitude: { type: "f", value: 1.0 }, color: { type: "c", value: new THREE.Color( 0xffffff ) }, texture: { type: "t", value: 1, texture: THREE.ImageUtils.loadTexture('/img/spark1.png') } };
        uniforms.texture.texture.wrapS = uniforms.texture.texture.wrapT = THREE.RepeatWrapping;

		var shaderMaterial = new THREE.ShaderMaterial( {
			uniforms: 		uniforms,
			attributes:	    attributes,
			vertexShader:   document.getElementById( 'vertexshader' ).textContent,
			fragmentShader: document.getElementById( 'fragmentshader' ).textContent,
			blending:       THREE.AdditiveBlending
		});

		//
        var values_sizes = attributes.size.value;
        var values_color = attributes.ca.value;
        var i = -1;

		//add the data to the global system variable
		systems = data;

		//loop through each system setting it up and plotting it on the map
		for (key in systems)
		{
			//apply the index
			systems[key].index = (i++);
			
			//sort out the systems position etc
			x = systems[key].x / 2e15; maxX = Math.max(x, maxX);
			y = systems[key].y / 2e15; maxY = Math.max(y, maxY);
			z = systems[key].z / 2e15; maxZ = Math.max(z, maxZ);

			//add the system in its rightful place
			geometry.vertices.push(new THREE.Vertex(new THREE.Vector3(x, y, z)));

			//set the systems colour
			values_color[i] = new THREE.Color(system_colours[systems[key].sec]);
			values_color[i].setRGB( 0.40, 0.40, 0.40 );
			values_sizes[i] = 10;

			//setup the base stuff for the blinking - the more blinks the bigger
			systems[key].blink = function() { this.lum += 0.75; };
			systems[key].lum = 0.25;
		}

		//add particles
        particles = new THREE.ParticleSystem( geometry, shaderMaterial );
        particles.sortParticles = true;
        scene.add( particles );
    });
}
