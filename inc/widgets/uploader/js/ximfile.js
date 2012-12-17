/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */


 (function(X) {
 	var numfiles = 0;

 	X.ximfile = Object.xo_create({

 			data: null,
 			url: null,
 			element: null,
 			container: null,
 			fname: null,
 			fsize: 0,
 			ftype: null,
 			uploaded: false,
 			index: 0,
			option: 0,
			result: 'ok',
			msg: '',
			urlcheckname: null,
			urlgetpreview: null,

 			_init: function(options) {

 				this.element = options._element;
 				this.container = options._container;
            this.fname = options.file.name.replace(/ /g, "_");
				this.fsize = options.file.size;
 				this.ftype = options.file.type;
				this.urlcheckname = options.urlcheckname;
				this.urlgetpreview = options.urlgetpreview;
				
				this.index = ++numfiles;

 		      //add element html to list
		      var list = $('.xim-loader-list', this.container);
				list.append(this._html(this.index) );
				this.element = $('li:last', list);
				$(this.element).data("ximfile", this);


 				//add element file to list
 			if(window.FileReader) {	
		      var reader_data = new FileReader();
		    	reader_data.onload = (function(ximfile) {
		        return function(e) {
      		    //ximfile(is object ximfile) | this(object FileReader)
      		    ximfile.data = e.target.result;
					//console.log("this:", this, "file:", ximfile, "data:", ximfile.data);
		        };
		      })(this);
  		    	reader_data.readAsBinaryString(options.file);

		   	var reader_url = new FileReader();
	   	   reader_url.onload = (function(ximfile) {
	   	   	return function(e) {
	   	   		ximfile.url = e.target.result;
	   	  			//console.log("this:", this, "file:", ximfile, "url:", ximfile.url, "type:",ximfile.ftype );
						ximfile.setImage();
	   	   	};
	   	   })(this);

			   reader_url.readAsDataURL(options.file);
			}
			else{
				this.data = options.file;
			}
				this.checkname();
			
			//Safari Preview test
			if(!window.FileReader) {
			 if(this.ftype.indexOf("image") != -1) { //Only make preview if file is an image
				var url = this.urlgetpreview;
				var  xmlhttprequest = new XMLHttpRequest();
				
				url += "&up=true";

				xmlhttprequest.open("POST", url, true);
				xmlhttprequest.onreadystatechange = function() {
					 if (xmlhttprequest.readyState == 4) {
						if ((xmlhttprequest.status >= 200 && xmlhttprequest.status <= 200) || xmlhttprequest.status == 304) {
						  if (xmlhttprequest.responseText != "") {

							  var result = $.parseJSON(xmlhttprequest.responseText);
 			         		  if(result.status == "ok") {
 			         		  	this.url = result.data;
 			         		  	this.setImage();
 			         		  }
						  }
						}
					 }
				  }.bind(this);


				xmlhttprequest.setRequestHeader('XIM-FILENAME', unescape(encodeURIComponent(this.getName())));
			  	xmlhttprequest.setRequestHeader('XIM-SIZE', this.getSize());
			  	xmlhttprequest.setRequestHeader('XIM-TYPE', this.getType());
			  	xmlhttprequest.send(this.getData());

				}
			 }
			//End preview
 			},


			checkname: function() {
				$('span.xim-loader-options', this.element).html('');
				var url = this.urlcheckname;
				url += "&name="+unescape(encodeURIComponent(this.getName()));

				Object.getJSON(url,  {onComplete:function(data) {
					 if( "nok" == data.status ) {
						var html = this._html_options();
						$('span.xim-loader-options', this.element).append(html);
						this.option = 1;
						$('span.xim-loader-options select', this.element).change(function (e) {
						  var value = e.currentTarget.value;
						  if(2 == value) {
							  var name = prompt(_("Write a new name for this file:"), this.getName() );

								if ( null == name ||  '' == name || name == this.getName() ) {
									 $('span.xim-loader-options select', this.element).val("1");
									 e.preventDefault();
									 e.stopPropagation();
									 return false;
								}

								this.option = 0;
								this.setName( name  );
								this.checkname();
						  }
						}.bind(this));
					 }
			  }.bind(this)});
			},

		   setName:  function(name) {
				this.fname = name;

				var html = ''
				if( this.ftype.indexOf("image") != -1 )  {
					 html += '<img src="#"  />';
				}
				html += this.fname;
				$('span.xim-loader-name', this.element).html(html);

				if( this.ftype.indexOf("image") != -1 )  {
					this.setImage();
				}

			},

			setImage: function() {
				  if( this.ftype.indexOf("image") != -1 )
						$('img', this.element).attr("src",  this.url );

				  $('img', this.element).show();

				  $('img', this.element).click(function (event) {
						var win = window.open(this.src,'prev_image','width=400,height=400')
						win.focus();
				  });
			},

 			getName:  function() { return this.fname; },
 			getSize:  function() { return this.fsize; },
 			getType:  function() { return this.ftype; },
 			getIndex: function() { return this.index; },
 			getData:  function() { return this.data;  },

 			remove: function() {
 				this.element.remove();
 				delete this;
 			},

 			_size: function() {
 				//only 1 decimal
				 var size = Math.round(parseFloat(this.fsize / 1024, 10)*10,2)/10;
 				 if ( size < 1 ) {
		 	  		return this.fsize+" Bytes";
		 		  }else if( size < 1024 ) {
		 	  	 	return size+" KBytes";
		 	  	 }else {
		 	  	 	var size = Math.round(parseFloat(size / 1024, 10)*10,2)/10;
		 	  	 	if( size < 1024 ) {
		 	  		 	return size+" MBytes";
					}else {
						var size = Math.round(parseFloat(size / 1024, 10)*10,2)/10;
		 	  		 	return size+" GBytes";
					}
		 	  	 }
 			},

 			upload: function(url) {
				 var boundary = "ximdex";
				 var dashes = "--";
				 var crlf='\r\n'
				 var  xhr = new XMLHttpRequest();

				  url += xhr.sendAsBinary != null ? "&option="+this.option : "&option="+this.option+"&up=true";

				  xhr.open("POST", url, true);
				  
				  //xhr.setRequestHeader("Content-Length", this.getSize());

				  xhr.upload.addEventListener("progress", function(e) {
			       if (e.lengthComputable) {
	        			  var currentState = Math.round((e.loaded * 100) / e.total);
	         		  $('div.xim-loader-progress', this.element).addClass("upload");
	         		  $('div.xim-loader-progress', this.element).width(currentState+"%");
	         		  $('div.xim-loader-progress', this.element).html(currentState+"%");
        		    }
		        }.bind(this), false);


				  xhr.onreadystatechange = function() {
					 if (xhr.readyState == 4) {
						if ((xhr.status >= 200 && xhr.status <= 200) || xhr.status == 304) {
						  if (xhr.responseText != "") {

								 var result = $.parseJSON(xhr.responseText);
 			         		  $('div.xim-loader-progress', this.element).width("100%");
								  this.result = result.status;
								  this.msg = result.msg;
								 if("ok" == this.result) {
	 		  	         		  $('div.xim-loader-progress', this.element).addClass("upload");
	    		         		  $('div.xim-loader-progress', this.element).html(_("completed"));
								 }else {
					 		  	     $('div.xim-loader-progress', this.element).addClass("error");
 					 		  	     $('div.xim-loader-progress', this.element).attr("title", this.msg );
	    		         		  $('div.xim-loader-progress', this.element).html("error");
								 }

								 $(this.element).append(this._html_uploaded());

								 $(this.element).trigger("fileUploaded", [{file: this, result: result}] );

								 //alert(result["msg"]);
						  	//Deleting the previous element from download list (if it exists)
							// If not more elements left apart the one just uploaded,
							// it is deleted and an event of uploaded files is sent to send the form
						  	// Then, per each file uploading an event (por example, to send the next) could be sent.
							// Now, there is not order and they are sent in a synchronized way, one after one.
						  }
						}
					 }
				  }.bind(this);



		 	  var body = dashes+boundary+crlf;
			  body += "Content-Disposition: form-data; ";
			  body += " name='ximfile'; filename=\"" + unescape(encodeURIComponent(this.getName())) + "\""+crlf;
			  body +=  "Content-Type: application/octet-stream"+crlf+crlf;
			  body += this.getData()+crlf;
			  body += dashes+boundary+dashes;

			  //Firefox
			  if(xhr.sendAsBinary != null) {
			  	// simulate a file MIME POST request.
  			    xhr.setRequestHeader("Content-Type","multipart/form-data; boundary="+boundary);
  			    //console.log("Calling with sendAsBinary");
			  	xhr.sendAsBinary(body);
			  }
			  else { //Browsers that don't support sendAsBinary yet
			  	xhr.setRequestHeader('XIM-FILENAME', unescape(encodeURIComponent(this.getName())));
			  	xhr.setRequestHeader('XIM-SIZE', this.getSize());
			  	xhr.setRequestHeader('XIM-TYPE', this.getType());
			  	xhr.send(this.getData());
			  }

			  return true;

 			},

 			_html: function() {
	 	     var html = '<li class="xim-loader-file">';
	 	     html += '<input type="checkbox" />';
	 	     html += '<span class="xim-loader-name">';
	 	      if( this.ftype.indexOf("image") != -1 )  {
		 	     	html += '<img src="#" />';
		 	  }
		 	  html += this.fname+'</span>';
	 	     html += '<span class="xim-loader-size">'+this._size()+'</span>';
		     html += '<span class="xim-loader-progress"><div class="xim-loader-progress"></div></span>';
  	 	     html += '<span  class="xim-loader-options"></span>';
	 	     html += '</li>';

	 	     return html;
			},

			_html_options: function() {
				  var html = '<select name="ximfile[options][]">';
				  html += '<option value="1">'+_("Overwrite")+'</option>';
				  html += '<option value="2">'+_("Rename")+'</option>';
				  html += '</select>';

				  return html;
			},

			_html_uploaded: function() {
				var html = '<input type="hidden" name="ximfile['+this.result+'][msg][]" value="'+this.msg+'" />';
				html += '<input type="hidden" name="ximfile['+this.result+'][name][]" value="'+this.fname+'" />';

				return html;
			},

 	});
 })(X);
