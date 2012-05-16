var defaultFileTypes;
function setFileTypes( fileTypes ) {
	defaultFileTypes = fileTypes;
}
function TestFileType( fileName, fileTypes ) {
	if (!fileName) return;
	if (!fileTypes) {
		fileTypes = defaultFileTypes;
	}
	dots = fileName.split(".")
	//get the part AFTER the LAST period.
	fileType = dots[dots.length-1];
	if ( fileTypes.join("#").toLowerCase().indexOf(fileType.toLowerCase()) == -1 ) {
		alert("Please only upload files that end in types: \n\n." + (fileTypes.join(" .")) + "\n\nPlease select a new file and try again.");
	}
}