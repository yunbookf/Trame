"use strict";
const NodeFS = require("fs");
const NodePath = require("path");
const async = require("async");
require("langext");
const SCRIPT_VERSION = "0.1.0-a1";
const ACTIONS_ROOT = "app/actions";
const SRT_PATH = "app/boot/static-router-table.php";
const DRT_PATH = "app/boot/dynamic-router-table.php";
const TAB = "    ";
let actionMethodMap = {};
let filesEntityTable = [];
let actionFiles = [];
let routerFiles = [];
class RouterTable {
    constructor(name) {
        this.name = name;
        this.simple = {};
        this.dynamic = [];
    }
    registerSimpleAction(filePath, uri) {
        console.info(`    - Registered: ${this.name} ${uri} -> ${filePath}`);
        this.simple[uri] = filePath;
    }
    registerDynamicAction(filePath, uri, callback) {
        let drr = RouterTable.compileDynamicRule(uri, filePath);
        if (!drr) {
            return callback({
                "name": "SYNTAX-ERROR",
                "message": `Unrecognizable URI expression "${uri}".`
            });
        }
        this.dynamic.push(drr);
        return callback();
    }
    static compileDynamicRule(rule, filePath) {
        let drr = {
            "exp": null,
            "variables": [],
            "path": filePath
        };
        let varTypes = [];
        let i = 0;
        drr.exp = new RegExp("^" + RegExp.escape(rule.replace(/\{[#\*\$%]\w+\}/g, function (el) {
            varTypes.push(el[1]);
            drr.variables.push(el.substr(2, el.length - 3));
            return "%%VB%%";
        })).replace(/%%VB%%/g, function () {
            switch (varTypes[i++]) {
                case "#": return "(\\d+)";
                case "%": return "(\\d+|\\d+\\.\\d+)";
                case "$": return "([^\\/]+)";
                case "*": return "(.+)";
            }
        }) + "$");
        return drr;
    }
}
/**
 * Configurations
 */
let optHTTPMethodStatus = {};
let routerTables = {};
let strictMode = false;
let ignoreInvalidFile = false;
let enabledDynamic = true;
let enabledAPCu = false;
/**
 * These methods are enabled by default.
 */
for (let httpMethod of ["ALL", "GET", "POST", "PUT", "PATCH", "DELETE"]) {
    optHTTPMethodStatus[httpMethod] = true;
    routerTables[httpMethod] = new RouterTable(httpMethod);
}
/**
 * These methods are disabled by default.
 */
for (let httpMethod of ["HEAD", "OPTIONS", "CONNECT"]) {
    optHTTPMethodStatus[httpMethod] = false;
}
const HTTP_METHODS = ["ALL", "GET", "POST", "PUT", "PATCH", "DELETE", "HEAD", "OPTIONS", "CONNECT"];
console.info(`Trame Static Router Table Build Tools v${SCRIPT_VERSION}\n`);
/**
 * Handle the arguments from console.
 */
for (let i = 2; i < process.argv.length; i++) {
    let argv = process.argv[i];
    switch (argv) {
        case "--strict-mode":
            console.info("    * Strict Mode is on.\n");
            strictMode = true;
            break;
        case "--ignore-other-files":
            ignoreInvalidFile = true;
            break;
        case "--enable-apcu":
            console.info("    * APCu support is on.\n");
            enabledAPCu = true;
            break;
        case "--disable-dynamic":
            console.info("    * Dynamic router has been disabled.\n");
            enabledDynamic = false;
            break;
        case "--enable-all-http-methods":
            for (let httpMethod of HTTP_METHODS) {
                optHTTPMethodStatus[httpMethod] = true;
                routerTables[httpMethod] = new RouterTable(httpMethod);
            }
            console.info("    * All HTTP methods filter have been reset to be enabled.\n");
            break;
        case "--disable-all-http-methods":
            for (let httpMethod of HTTP_METHODS) {
                optHTTPMethodStatus[httpMethod] = false;
                delete routerTables[httpMethod];
            }
            console.info("    * All HTTP methods filter have been reset to be disabled.\n");
            break;
        default:
            if (argv.startsWith("--enable-http-")) {
                let httpMethod = argv.substr(14);
                if (HTTP_METHODS.indexOf(httpMethod) >= 0) {
                    optHTTPMethodStatus[httpMethod] = true;
                    routerTables[httpMethod] = new RouterTable(httpMethod);
                    console.info(`    * HTTP methods filter "${httpMethod}" has been enabled.\n`);
                }
                else {
                    console.warn(` - Invalid parameter "${argv}".`);
                }
            }
            else if (argv.startsWith("--disable-http-")) {
                let httpMethod = argv.substr(15);
                if (HTTP_METHODS.indexOf(httpMethod) >= 0) {
                    optHTTPMethodStatus[httpMethod] = false;
                    delete routerTables[httpMethod];
                    console.info(`    * HTTP methods filter "${httpMethod}" has been disabled.\n`);
                }
                else {
                    console.warn(`Invalid parameter "${argv}".`);
                }
            }
    }
}
/**
 * Concat two path into one.
 */
function concatPath(a, b) {
    if (NodePath.sep === "\\") {
        return NodePath.normalize(a + "/" + b).replace(/\\/g, "/");
    }
    else {
        return NodePath.normalize(a + "/" + b);
    }
}
/**
 * Concat two URI into one.
 */
function concatURI(a, b) {
    let started = b[0] === "/";
    let ended = a[a.length - 1] === "/";
    if (started && ended) {
        return `${a}${b.substr(1)}`;
    }
    if (started || ended) {
        return `${a}${b}`;
    }
    return `${a}/${b}`;
}
/**
 * Fix the URI if ends with "index" or is empty.
 */
function fixURI(uri) {
    if (uri[uri.length - 1] === "/") {
        uri = uri.substr(0, uri.length - 1);
    }
    if (uri.endsWith("index")) {
        uri = uri.substr(0, uri.length - 6);
    }
    if (uri.length === 0) {
        uri = "/";
    }
    return uri;
}
/**
 * Add a file into simple table.
 */
function addDynamicRule(spMethods, uri, file, callback) {
    if (!uri.match(/\{[#\*\$%]\w+\}/)) {
        return addSimpleRule(spMethods, uri, file, callback);
    }
    let method = spMethods[0];
    if (optHTTPMethodStatus[method]) {
        return routerTables[method].registerDynamicAction(file, uri, function (err) {
            if (err) {
                if (strictMode) {
                    return callback(err);
                }
                console.warn(`[?] Warning: ${err.message}.`);
                return callback();
            }
            console.info(`    - Registered ${method} ${uri} -> ${file}`);
            return callback();
        });
    }
    else if (strictMode) {
        return callback({
            "name": "UNAVAIABLE-FILTER",
            "message": `HTTP methods filter "${method}" is disabled.`
        });
    }
    else {
        console.warn(`[?] Warning: HTTP methods filter "${method}" is disabled.`);
        return callback();
    }
}
/**
 * Add a file into simple table.
 */
function addSimpleRule(spMethods, uri, file, callback) {
    async.eachOfSeries(spMethods, function (method, key, next) {
        if (optHTTPMethodStatus[method]) {
            routerTables[method].registerSimpleAction(file, uri);
            return next();
        }
        else if (strictMode) {
            return next({
                "name": "UNAVAIABLE-FILTER",
                "message": `HTTP methods filter "${method}" is disabled.`
            });
        }
        else {
            console.warn(`[?] Warning: HTTP methods filter "${method}" is disabled.`);
            return next();
        }
    }, callback);
}
/**
 * Scan all files in actions directory.
 */
function scanRoutableFiles(dir, callback) {
    NodeFS.readdir(dir, function (err, items) {
        if (err) {
            return callback(err);
        }
        async.eachOfSeries(items, function (item, key, next) {
            if (item === "." || item === "..") {
                return next();
            }
            let path = concatPath(dir, item);
            NodeFS.stat(path, function (err, stats) {
                if (err) {
                    return next(err);
                }
                if (stats.isDirectory()) {
                    if (item[0] !== ".") {
                        scanRoutableFiles(path, next);
                    }
                    else {
                        next();
                    }
                }
                else {
                    if (item === ".router") {
                        routerFiles.push(path);
                    }
                    else {
                        if (item[0] !== ".") {
                            actionFiles.push(path);
                        }
                        filesEntityTable.push(path);
                    }
                    next();
                }
            });
        }, callback);
    });
}
/**
 * Build the table of simple URI->File mappings.
 */
function buildSimpleTable(root, next) {
    async.eachOfSeries(actionFiles, function (file, key, next) {
        let matches = file.match(/(.(GET|POST|PUT|PATCH|DELETE|HEAD|CONNECT|OPTIONS))*\.php$/);
        if (!matches) {
            if (ignoreInvalidFile) {
                console.warn(`[?] Warning: File "${file}" is not a php script file.`);
                return next();
            }
            else {
                return next({
                    "name": "INVALID-SCRIPT",
                    "message": `File "${file}" is not a php script file.`
                });
            }
        }
        return addSimpleRule(matches[1] ? matches[0].substr(1, matches[0].length - 5).split(".") : ["ALL"], fixURI(file.substr(root.length, file.length - root.length - matches[0].length)), file, next);
    }, next);
}
/**
 * Parse the ".router" files.
 */
function handleRouterFile(root, cwd, rules, file, callback) {
    async.eachOfSeries(rules, function (rule, line, next) {
        rule = rule.trim();
        if (rule.length === 0) {
            return next();
        }
        let matches = rule.match(/^([A-Z]+)\s+([\S]+)\s+([\S]+)$/);
        if (matches && HTTP_METHODS.indexOf(matches[1]) > -1) {
            let path = concatPath(cwd, matches[3]);
            NodeFS.exists(path, function (exist) {
                if (!exist) {
                    return next({
                        "name": "NOT-FOUND",
                        "message": `File "${path}" doesn't exist.`
                    });
                }
                addDynamicRule([matches[1]], fixURI(concatURI(NodePath.dirname(file).substr(root.length), matches[2])), path, next);
            });
            return;
        }
        if (strictMode) {
            return next({
                "name": "SYNTAX-ERROR",
                "message": `Syntax error in file "${file}" line ${line + 1}.`
            });
        }
        else {
            console.warn(`[?] Warning: Syntax error in file "${file}" line ${line + 1}.`);
            return next();
        }
    }, callback);
}
/**
 * Build the table of dynamic mappings.
 */
function buildDynamicTable(root, callback) {
    async.eachOfSeries(routerFiles, function (file, key, next) {
        NodeFS.readFile(file, "utf-8", function (err, data) {
            data.replace(/\r\n/g, "\n").replace(/\r/g, "\n");
            if (err) {
                return next({
                    "name": "BAD-ROUTER-FILE",
                    "message": `Failed to read the file "${file}".`
                });
            }
            handleRouterFile(root, NodePath.dirname(file), data.replace(/\r\n/g, "\n").replace(/\r/g, "\n").split("\n"), file, next);
        });
    }, callback);
}
function generateStaticTable(root, tablePath, callback) {
    let tables = [];
    for (let method in routerTables) {
        let table = routerTables[method];
        let maps = [];
        for (let uri in table.simple) {
            let path = table.simple[uri];
            maps.push(`${TAB.repeat(2)}'${uri}' => '${path}'`);
        }
        tables.push(`${TAB}'${method}' => [
${maps.join(",\n")}
${TAB}]`);
    }
    let srt = `<?php
return [
${tables.join(",\n")}
];
`;
    NodeFS.writeFile(tablePath, srt, callback);
}
function generateDynamicTable(root, tablePath, callback) {
    let tables = [];
    for (let method in routerTables) {
        let table = routerTables[method];
        let maps = [];
        for (let dr of table.dynamic) {
            let exp = JSON.stringify(dr.exp.source);
            exp = `/${exp.substr(1, exp.length - 2)}/`;
            maps.push(`${TAB.repeat(2)}[
${TAB.repeat(3)}'expr' => '${exp}',
${TAB.repeat(3)}'path' => '${dr.path}',
${TAB.repeat(3)}'vars' => ${JSON.stringify(dr.variables)}
${TAB.repeat(2)}]`);
        }
        tables.push(`${TAB}'${method}' => [
${maps.join(",\n")}
${TAB}]`);
    }
    let drt = `<?php
return [
${tables.join(",\n")}
];
`;
    NodeFS.writeFile(tablePath, drt, callback);
}
async.series([
    function (next) {
        scanRoutableFiles(ACTIONS_ROOT, next);
    },
    function (next) {
        console.info(`File scan completed, found:\n
    ${filesEntityTable.length} Actions files
    ${actionFiles.length} Simple Actions files
    ${routerFiles.length} Advanced Router files\n
Building Simple Router Table:\n`);
        buildSimpleTable(ACTIONS_ROOT, next);
    },
    function (next) {
        if (enabledDynamic) {
            console.info("\nBuilding Dynamic Router Table:\n");
            buildDynamicTable(ACTIONS_ROOT, next);
        }
        else {
            next();
        }
    },
    function (next) {
        generateStaticTable(ACTIONS_ROOT, SRT_PATH, next);
    },
    function (next) {
        generateDynamicTable(ACTIONS_ROOT, DRT_PATH, next);
    }
], function (err) {
    console.log("");
    if (err) {
        console.error(`[!] Error [${err.name}]: ${err.message}.`);
    }
    else {
        console.info("Router table has been successfully built.");
    }
});
//# sourceMappingURL=build-router-table.js.map