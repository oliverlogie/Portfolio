/**
 * @param element
 *        id of the html element that should be set visible or hidden
 */
function showhide_box(element){
  obj = document.getElementById(element);
  var old_status = obj.style.visibility;
  if(old_status == "hidden"){
    obj.style.visibility = "visible";
    obj.style.display = "block";
  }
  else{
    obj.style.visibility = "hidden";
    obj.style.display = "none";
  }
}