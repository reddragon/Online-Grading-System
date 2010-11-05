var clockID = 0;
var hour = 0;
var min = 0;
var sec = 10;

function startClock(hours, mins, secs) {
	hour = hours;
	min = mins;
	sec = secs;
	if (clockID == 0) {
		updateClock();
	}
}

function formatTime(h, m, s) {
    var str = "";
	if (h < 10)
		str += "0";
	str += h;
	str += ":";
	if (m < 10)
		str += "0";
	str += m;
	str += ":";
	if (s < 10)
		str += "0";
	str += s;
	return str;
}

function updateClock() {
	if (sec == 0) {
		if (min == 0) {
			if (hour == 0) {
				alert("Your time has expired! Reloading contest status...");
				window.location = document.location;
				return;
			}
			--hour;
			min = 60;
		}
		--min;
		sec = 60;
	}
	--sec;
	
	str = formatTime(hour, min, sec);
	
	timer1 = getObj('timer');
	if (timer1) {
	   // doing this simply gets rid of the timer-field form
	   timer1.innerHTML = "Time left: <span class=\"timerValue\">" + str + "</div>";
    } else {
        document.frmClock.fieldTimer.value = str;
    }
	clockID = setTimeout("updateClock()", 1000);
}

function stopClock() {
	if (clockID) {
		clearTimeout(clockID);
		clockID = 0;
	}
}
