//Sort stats table DESC
function sortTable(td_class, a_class){
    var tbl = document.getElementById("ppc_stats_table").tBodies[0];
    var store = [];
    for(var i=0, len=tbl.rows.length; i<len; i++){
        var row = tbl.rows[i];
        var cell = row.getElementsByClassName(td_class);
        var text = cell[0].firstChild;
        var sortnr = parseFloat(text.textContent || text.innerText);
        if(!isNaN(sortnr)) store.push([sortnr, row]);
    }
    store.sort(function(x,y){
        return y[0] - x[0];
    });
    for(var i=0, len=store.length; i<len; i++){
        tbl.appendChild(store[i][1]);
    }
    store = null;
}

jQuery(document).ready(function($) {
    //Sort stats table on page load for column 'due_pay'
    if($('#ppc_stats_table').length != 0) {
        //sortTable('due_pay', 'ppc_payment_column');
    }
});