var canvas = document.getElementById(inputId[1]);
var ctx = canvas.getContext('2d');
var dataComp = '';
var sizeCheck = [512000,204800,153600];
var x="0";
var y="0";
var reSize="";

//拡張子がjpegの場合
if(extention == 'jpeg'){

	//Rendering megapix image into specified target element
	MegaPixImage.prototype.render = function(target, options) {
		if (this.imageLoadListeners) {
			var _this = this;
			this.imageLoadListeners.push(function() { _this.render(target, options); });
			return;
		}
		options = options || {};
		var imgWidth = this.srcImage.naturalWidth, imgHeight = this.srcImage.naturalHeight,
		width = options.width, height = options.height,
		maxWidth = options.maxWidth, maxHeight = options.maxHeight,
		doSquash = !this.blob || this.blob.type === 'image/jpeg';
		if (width && !height) {
			height = (imgHeight * width / imgWidth) << 0;
		} else if (height && !width) {
			width = (imgWidth * height / imgHeight) << 0;
		} else {
			width = imgWidth;
			height = imgHeight;
		}
		if (maxWidth && width > maxWidth) {
			width = maxWidth;
			height = (imgHeight * width / imgWidth) << 0;
		}
		if (maxHeight && height > maxHeight) {
			height = maxHeight;
			width = (imgWidth * height / imgHeight) << 0;
		}
		var opt = { width : width, height : height };
		for (var k in options) opt[k] = options[k];

		var tagName = target.tagName.toLowerCase();
		if (tagName === 'img') {
			target.src = renderImageToDataURL(this.srcImage, opt, doSquash);
		} else if (tagName === 'canvas') {
			renderImageToCanvas(this.srcImage, target, opt, doSquash);
		}
		if (typeof this.onrender === 'function') {
			this.onrender(target);
		}

		// 結果
		imgData[inputId[0]] = canvas.toDataURL();
	};


	var mpImg = new MegaPixImage(fileData[0]);
	mpImg.render(canvas, { maxWidth: 450, maxHeight: 450 });

}else if(extention == 'png'){
//拡張子がpngの場合

	//ファイルサイズが500KBより大きい場合
	if (fileData[0].size > sizeCheck[0]){
		//画像ファイルの圧縮率算出
		dataComp = 100-((sizeCheck[0]/fileData[0].size)*100);

		//圧縮率に応じて画像サイズをリサイズする
		if (80<dataComp && dataComp<100){
			reSize = 10;
			canvasResize(reSize);
		}else if (50<dataComp && dataComp<80){
			reSize = 8;
			canvasResize(reSize);
		}else if (30<dataComp && dataComp<50){
			reSize = 6;
			canvasResize(reSize);
		}else if (0<dataComp && dataComp<30){
			reSize = 4;
			canvasResize(reSize);
		}

		imgData[inputId[0]] = canvas.toDataURL();

		var fileSize = localStorageCustom.getStorage(UPLOADIMG_KEY[1].key);
		var resizeCount = 10;

		//base64へエンコードしたデータが、150KBより小さい場合に圧縮サイズ変更処理を繰り返す。
		while(fileSize.length < sizeCheck[2] && resizeCount != 0){

			reSize = resizeCount;
			canvasResize(reSize);
			imgData[inputId[0]] = canvas.toDataURL();
			localStorageCustom.setStorage(UPLOADIMG_KEY[1].key,imgData[inputId[0]]);

			fileSize = localStorageCustom.getStorage(UPLOADIMG_KEY[1].key);

			resizeCount--;
		}
	}else if(fileData[0].size > sizeCheck[1]){
		//300KBより小さく、200KBより大きい場合
		//変換後のファイルサイズが300KB以上になる、ストレージに保存する際にファイルが壊れる為リサイズする
		reSize = 2;
		canvasResize(reSize);
	}else{
		//ファイルサイズが200KBより小さい場合、圧縮を行わない
		canvas.setAttribute("width",img.width);
		canvas.setAttribute("height",img.height);
		ctx.drawImage(img,x,y,img.width,img.height);
	}

	imgData[inputId[0]] = canvas.toDataURL();
	imgId[inputId[0]] = inputId[0];
	localStorageCustom.setStorage(UPLOADIMG_KEY[1].key,imgData[inputId[0]]);
	var fileSize = localStorageCustom.getStorage(UPLOADIMG_KEY[1].key);
	var resizeCount = 2;

	//base64へエンコードしたデータが、300KBより大きい場合に圧縮処理を繰り返す。
	while(fileSize.length > sizeCheck[0]){

		reSize = resizeCount;
		canvasResize(reSize);

		imgData[inputId[0]] = canvas.toDataURL();
		imgId[inputId[0]] = inputId[0];

		localStorageCustom.setStorage(UPLOADIMG_KEY[1].key,imgData[inputId[0]]);
		fileSize = localStorageCustom.getStorage(UPLOADIMG_KEY[1].key);

		resizeCount++;
	}
	localStorageCustom.singleDeleteStorage(UPLOADIMG_KEY[1].key);
}

/**
 * 画像圧縮処理。
 * canvasデータを縮小する。
 * @param {int} reSize 縮小する計算値
 * @example
 * canvasResize(reSize)
 */
function canvasResize(reSize){
	canvas.setAttribute("width",img.width/reSize);
	canvas.setAttribute("height",img.height/reSize);
	ctx.drawImage(img,x,y,img.width/reSize,img.height/reSize);
}