var cache = {};

function save(path, node) {
    var export_name = node.getElementsByClassName("export_name")[0].value;
    var change_mode = node.getElementsByClassName("change_mode")[0].value;
    var type = node.getElementsByClassName("type")[0].value;
    var language = node.getElementsByClassName("language")[0].value;
    cache[path] = {
        export_name: export_name,
        change_mode: change_mode,
        type: type,
        language: language,
    }

}

function execute(path, node) {
    var export_name = node.getElementsByClassName("export_name")[0].value;
    var change_mode = node.getElementsByClassName("change_mode")[0].value;
    var type = node.getElementsByClassName("type")[0].value;
    var language = node.getElementsByClassName("language")[0].value;

    // store these value
    var post = {
        path: path,
        export_name : export_name,
        change_mode : change_mode,
        type : type,
        language : language,
        cache   : cache
    };

    // build the asset
    open("POST", "/Resource/build", post);
}
/**
 * @link http://stackoverflow.com/questions/17793183/how-to-replace-window-open-with-a-post
 */
function open(verb, url, data, target) {
    var form = document.createElement("form");

    form.action = url;
    form.method = verb;
    form.target = target || "_blank";

    if (data)
    {
        for (var key in data)
        {
            if (!data.hasOwnProperty(key))
                continue;

            var input   = document.createElement("textarea");
            input.name  = key;
            input.value = typeof data[key] === "object" ? JSON.stringify(data[key]) : data[key];

            form.appendChild(input);
        }
    }

    document.body.appendChild(form);

    form.style.display = 'none';
    form.submit();

    document.body.removeChild(form);
}
