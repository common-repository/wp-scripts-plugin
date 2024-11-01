//MooTools More, <http://mootools.net/more>. Copyright (c) 2006-2008 Valerio Proietti, <http://mad4milk.net>, MIT Style License.

Hash.Cookie=new Class({Extends:Cookie,options:{autoSave:true},initialize:function(B,A){this.parent(B,A);this.load();},save:function(){var A=JSON.encode(this.hash);
if(!A||A.length>4096){return false;}if(A=="{}"){this.dispose();}else{this.write(A);}return true;},load:function(){this.hash=new Hash(JSON.decode(this.read(),true));
return this;}});Hash.Cookie.implement((function(){var A={};Hash.each(Hash.prototype,function(C,B){A[B]=function(){var D=C.apply(this.hash,arguments);if(this.options.autoSave){this.save();
}return D;};});return A;})());var Color=new Native({initialize:function(B,C){if(arguments.length>=3){C="rgb";B=Array.slice(arguments,0,3);}else{if(typeof B=="string"){if(B.match(/rgb/)){B=B.rgbToHex().hexToRgb(true);
}else{if(B.match(/hsb/)){B=B.hsbToRgb();}else{B=B.hexToRgb(true);}}}}C=C||"rgb";switch(C){case"hsb":var A=B;B=B.hsbToRgb();B.hsb=A;break;case"hex":B=B.hexToRgb(true);
break;}B.rgb=B.slice(0,3);B.hsb=B.hsb||B.rgbToHsb();B.hex=B.rgbToHex();return $extend(B,this);}});Color.implement({mix:function(){var A=Array.slice(arguments);
var C=($type(A.getLast())=="number")?A.pop():50;var B=this.slice();A.each(function(D){D=new Color(D);for(var E=0;E<3;E++){B[E]=Math.round((B[E]/100*(100-C))+(D[E]/100*C));
}});return new Color(B,"rgb");},invert:function(){return new Color(this.map(function(A){return 255-A;}));},setHue:function(A){return new Color([A,this.hsb[1],this.hsb[2]],"hsb");
},setSaturation:function(A){return new Color([this.hsb[0],A,this.hsb[2]],"hsb");},setBrightness:function(A){return new Color([this.hsb[0],this.hsb[1],A],"hsb");
}});function $RGB(C,B,A){return new Color([C,B,A],"rgb");}function $HSB(C,B,A){return new Color([C,B,A],"hsb");}function $HEX(A){return new Color(A,"hex");
}Array.implement({rgbToHsb:function(){var B=this[0],C=this[1],J=this[2];var G,F,H;var I=Math.max(B,C,J),E=Math.min(B,C,J);var K=I-E;H=I/255;F=(I!=0)?K/I:0;
if(F==0){G=0;}else{var D=(I-B)/K;var A=(I-C)/K;var L=(I-J)/K;if(B==I){G=L-A;}else{if(C==I){G=2+D-L;}else{G=4+A-D;}}G/=6;if(G<0){G++;}}return[Math.round(G*360),Math.round(F*100),Math.round(H*100)];
},hsbToRgb:function(){var C=Math.round(this[2]/100*255);if(this[1]==0){return[C,C,C];}else{var A=this[0]%360;var E=A%60;var F=Math.round((this[2]*(100-this[1]))/10000*255);
var D=Math.round((this[2]*(6000-this[1]*E))/600000*255);var B=Math.round((this[2]*(6000-this[1]*(60-E)))/600000*255);switch(Math.floor(A/60)){case 0:return[C,B,F];
case 1:return[D,C,F];case 2:return[F,C,B];case 3:return[F,D,C];case 4:return[B,F,C];case 5:return[C,F,D];}}return false;}});String.implement({rgbToHsb:function(){var A=this.match(/\d{1,3}/g);
return(A)?hsb.rgbToHsb():null;},hsbToRgb:function(){var A=this.match(/\d{1,3}/g);return(A)?A.hsbToRgb():null;}});var Group=new Class({initialize:function(){this.instances=Array.flatten(arguments);
this.events={};this.checker={};},addEvent:function(B,A){this.checker[B]=this.checker[B]||{};this.events[B]=this.events[B]||[];if(this.events[B].contains(A)){return false;
}else{this.events[B].push(A);}this.instances.each(function(C,D){C.addEvent(B,this.check.bind(this,[B,C,D]));},this);return this;},check:function(C,A,B){this.checker[C][B]=true;
var D=this.instances.every(function(F,E){return this.checker[C][E]||false;},this);if(!D){return ;}this.checker[C]={};this.events[C].each(function(E){E.call(this,this.instances,A);
},this);}});var Asset=new Hash({javascript:function(F,D){D=$extend({onload:$empty,document:document,check:$lambda(true)},D);var B=new Element("script",{src:F,type:"text/javascript"});
var E=D.onload.bind(B),A=D.check,G=D.document;delete D.onload;delete D.check;delete D.document;B.addEvents({load:E,readystatechange:function(){if(["loaded","complete"].contains(this.readyState)){E();
}}}).setProperties(D);if(Browser.Engine.webkit419){var C=(function(){if(!$try(A)){return ;}$clear(C);E();}).periodical(50);}return B.inject(G.head);},css:function(B,A){return new Element("link",$merge({rel:"stylesheet",media:"screen",type:"text/css",href:B},A)).inject(document.head);
},image:function(C,B){B=$merge({onload:$empty,onabort:$empty,onerror:$empty},B);var D=new Image();var A=$(D)||new Element("img");["load","abort","error"].each(function(E){var F="on"+E;
var G=B[F];delete B[F];D[F]=function(){if(!D){return ;}if(!A.parentNode){A.width=D.width;A.height=D.height;}D=D.onload=D.onabort=D.onerror=null;G.delay(1,A,A);
A.fireEvent(E,A,1);};});D.src=A.src=C;if(D&&D.complete){D.onload.delay(1);}return A.setProperties(B);},images:function(D,C){C=$merge({onComplete:$empty,onProgress:$empty},C);
if(!D.push){D=[D];}var A=[];var B=0;D.each(function(F){var E=new Asset.image(F,{onload:function(){C.onProgress.call(this,B,D.indexOf(F));B++;if(B==D.length){C.onComplete();
}}});A.push(E);});return new Elements(A);}});