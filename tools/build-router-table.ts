
import NodeFS = require("fs");
import NodePath = require("path");
import async = require("async");

import "langext";

const SCRIPT_VERSION: string = "0.1.0-a1";

const ACTIONS_ROOT: string = "app/actions";
const SRT_PATH: string = "app/boot/static-router-table.php";
const DRT_PATH: string = "app/boot/dynamic-router-table.php";
const TAB: string = "    ";

interface HTTPMethodTable<T> extends Dictionary<T> {
    "ALL"?: T;
    "GET"?: T;
    "POST"?: T;
    "PUT"?: T;
    "PATCH"?: T;
    "DELETE"?: T;
    "HEAD"?: T;
    "OPTIONS"?: T;
    "CONNECT"?: T;
}

let actionMethodMap: Dictionary<HTTPMethodTable<boolean>> = {};
let filesEntityTable: string[] = [];
let actionFiles: string[] = [];
let routerFiles: string[] = [];

interface DynamicRouterRule {

    "exp": RegExp;

    "variables": string[];

    "path": string;
}

class RouterTable {

    "simple": Dictionary<string>;

    "dynamic": DynamicRouterRule[];

    "name": string;

    public constructor(name: string) {

        this.name = name;

        this.simple = {};

        this.dynamic = [];
    }

    public registerSimpleAction(filePath: string, uri: string) {

        console.info(`    - Registered: ${this.name} ${uri} -> ${filePath}`);

        this.simple[uri] = filePath;
    }

    public registerDynamicAction(filePath: string, uri: string, callback: ErrorCallback): void {

        let drr: DynamicRouterRule = RouterTable.compileDynamicRule(uri, filePath);

        if (!drr) {

            return callback({
                "name": "SYNTAX-ERROR",
                "message": `Unrecognizable URI expression "${uri}".`
            });
        }

        this.dynamic.push(drr);

        return callback();
    }

    protected static compileDynamicRule(rule: string, filePath: string): DynamicRouterRule {

        let drr: DynamicRouterRule = {
            "exp": null,
            "variables": [],
            "path": filePath
        };

        let varTypes: string[] = [];
        let i: number = 0;

        drr.exp = new RegExp("^" + RegExp.escape(rule.replace(/\{[#\*\$%]\w+\}/g, function(el: string): string {

            varTypes.push(el[1]);
            drr.variables.push(el.substr(2, el.length - 3));

            return "%%VB%%";

        })).replace(/%%VB%%/g, function(): string {

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

let optHTTPMethodStatus: HTTPMethodTable<boolean> = {};

let routerTables: HTTPMethodTable<RouterTable> = {};

let strictMode: boolean = false;

let ignoreInvalidFile: boolean = false;

let enabledDynamic: boolean = true;

let enabledAPCu: boolean = false;

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

const HTTP_METHODS: string[] = ["ALL", "GET", "POST", "PUT", "PATCH", "DELETE", "HEAD", "OPTIONS", "CONNECT"];

console.info(`Trame Static Router Table Build Tools v${SCRIPT_VERSION}\n`);

/**
 * Handle the arguments from console.
 */
for (let i: number = 2; i < process.argv.length; i++) {

    let argv: string = process.argv[i];

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

            let httpMethod: string = argv.substr(14);

            if (HTTP_METHODS.indexOf(httpMethod) >= 0) {

                optHTTPMethodStatus[httpMethod] = true;
                routerTables[httpMethod] = new RouterTable(httpMethod);

                console.info(`    * HTTP methods filter "${httpMethod}" has been enabled.\n`);

            } else {

                console.warn(` - Invalid parameter "${argv}".`);
            }

        } else if (argv.startsWith("--disable-http-")) {

            let httpMethod: string = argv.substr(15);

            if (HTTP_METHODS.indexOf(httpMethod) >= 0) {

                optHTTPMethodStatus[httpMethod] = false;
                delete routerTables[httpMethod];

                console.info(`    * HTTP methods filter "${httpMethod}" has been disabled.\n`);

            } else {

                console.warn(`Invalid parameter "${argv}".`);
            }

        }
    }
}

/**
 * Concat two path into one.
 */
function concatPath(a: string, b: string) {

    if (NodePath.sep === "\\") {

        return NodePath.normalize(a + "/" + b).replace(/\\/g, "/");

    } else {

        return NodePath.normalize(a + "/" + b);
    }
}

/**
 * Concat two URI into one.
 */
function concatURI(a: string, b: string) {

    let started: boolean = b[0] === "/";
    let ended: boolean = a[a.length - 1] === "/";

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
function fixURI(uri: string): string {

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
function addDynamicRule(spMethods: string[], uri: string, file: string, callback: ErrorCallback): void {

    if (!uri.match(/\{[#\*\$%]\w+\}/)) {

        return addSimpleRule(spMethods, uri, file, callback);
    }

    let method = spMethods[0];

    if (optHTTPMethodStatus[method]) {

        return routerTables[method].registerDynamicAction(file, uri, function(err?: Error) {

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

    } else if (strictMode) {

        return callback({
            "name": "UNAVAIABLE-FILTER",
            "message": `HTTP methods filter "${method}" is disabled.`
        });

    } else {

        console.warn(`[?] Warning: HTTP methods filter "${method}" is disabled.`);

        return callback();
    }


}

/**
 * Add a file into simple table.
 */
function addSimpleRule(spMethods: string[], uri: string, file: string, callback: ErrorCallback): void {

    async.eachOfSeries<string>(spMethods, function(method: string, key: number, next: ErrorCallback): void {

        if (optHTTPMethodStatus[method]) {

            routerTables[method].registerSimpleAction(file, uri);

            return next();

        } else if (strictMode) {

            return next({
                "name": "UNAVAIABLE-FILTER",
                "message": `HTTP methods filter "${method}" is disabled.`
            });

        } else {

            console.warn(`[?] Warning: HTTP methods filter "${method}" is disabled.`);

            return next();
        }

    }, callback);
}

/**
 * Scan all files in actions directory.
 */
function scanRoutableFiles(dir: string, callback: ErrorCallback): void {

    NodeFS.readdir(dir, function(err: Error, items: string[]): void {

        if (err) {

            return callback(err);
        }

        async.eachOfSeries<string>(items, function(item: string, key: string, next: ErrorCallback): void {

            if (item === "." || item === "..") {

                return next();
            }

            let path: string = concatPath(dir, item);

            NodeFS.stat(path, function(err: Error, stats: NodeFS.Stats): void {

                if (err) {

                    return next(err);
                }

                if (stats.isDirectory()) {

                    if (item[0] !== ".") {

                        scanRoutableFiles(path, next);

                    } else {

                        next();
                    }

                } else {

                    if (item === ".router") {

                        routerFiles.push(path);

                    } else {

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
function buildSimpleTable(root: string, next: ErrorCallback): void {

    async.eachOfSeries<string>(actionFiles, function(file: string, key: number, next: ErrorCallback): void {

        let matches: RegExpMatchArray = file.match(/(.(GET|POST|PUT|PATCH|DELETE|HEAD|CONNECT|OPTIONS))*\.php$/);

        if (!matches) {

            if (ignoreInvalidFile) {

                console.warn(`[?] Warning: File "${file}" is not a php script file.`);

                return next();

            } else {

                return next({
                    "name": "INVALID-SCRIPT",
                    "message": `File "${file}" is not a php script file.`
                });

            }
        }

        return addSimpleRule(
            matches[1] ? matches[0].substr(1, matches[0].length - 5).split(".") : ["ALL"],
            fixURI(file.substr(root.length, file.length - root.length - matches[0].length)),
            file,
            next
        );
    }, next);
}

/**
 * Parse the ".router" files.
 */
function handleRouterFile(root: string, cwd: string, rules: string[], file: string, callback: ErrorCallback): void {

    async.eachOfSeries<string>(rules, function(rule: string, line: number, next: ErrorCallback): void {

        rule = rule.trim();

        if (rule.length === 0) {

            return next();
        }

        let matches: RegExpMatchArray = rule.match(/^([A-Z]+)\s+([\S]+)\s+([\S]+)$/);

        if (matches && HTTP_METHODS.indexOf(matches[1]) > -1) {

            let path: string = concatPath(cwd, matches[3]);

            NodeFS.exists(path, function(exist: boolean): void {

                if (!exist) {

                    return next({
                        "name": "NOT-FOUND",
                        "message": `File "${path}" doesn't exist.`
                    });
                }

                addDynamicRule(
                    [matches[1]],
                    fixURI(concatURI(NodePath.dirname(file).substr(root.length), matches[2])),
                    path,
                    next
                );
            });

            return;
        }

        if (strictMode) {

            return next({
                "name": "SYNTAX-ERROR",
                "message": `Syntax error in file "${file}" line ${line + 1}.`
            });

        } else {

            console.warn(`[?] Warning: Syntax error in file "${file}" line ${line + 1}.`);

            return next();
        }

    }, callback);
}

/**
 * Build the table of dynamic mappings.
 */
function buildDynamicTable(root: string, callback: ErrorCallback): void {

    async.eachOfSeries<string>(routerFiles, function(file: string, key: number, next: ErrorCallback): void {

        NodeFS.readFile(file, "utf-8", function(err: Error, data: string): void {

            data.replace(/\r\n/g, "\n").replace(/\r/g, "\n");

            if (err) {

                return next({
                    "name": "BAD-ROUTER-FILE",
                    "message": `Failed to read the file "${file}".`
                });
            }

            handleRouterFile(
                root,
                NodePath.dirname(file),
                data.replace(/\r\n/g, "\n").replace(/\r/g, "\n").split("\n"),
                file,
                next
            );

        });

    }, callback);
}

function generateStaticTable(root: string, tablePath: string, callback: ErrorCallback): void {

    let tables: string[] = [];

    for (let method in routerTables) {

        let table: RouterTable = routerTables[method];

        let maps: string[] = [];

        for (let uri in table.simple) {

            let path: string = table.simple[uri];

            maps.push(`${TAB.repeat(2)}'${uri}' => '${path}'`);

        }

        tables.push(`${TAB}'${method}' => [
${maps.join(",\n")}
${TAB}]`);

    }

    let srt: string = `<?php
return [
${tables.join(",\n")}
];
`;

    NodeFS.writeFile(tablePath, srt, callback);

}

function generateDynamicTable(root: string, tablePath: string, callback: ErrorCallback): void {

    let tables: string[] = [];

    for (let method in routerTables) {

        let table: RouterTable = routerTables[method];

        let maps: string[] = [];

        for (let dr of table.dynamic) {

            let exp: string = JSON.stringify(dr.exp.source);

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

    let drt: string = `<?php
return [
${tables.join(",\n")}
];
`;

    NodeFS.writeFile(tablePath, drt, callback);

}

async.series([

    function (next: ErrorCallback): void {

        scanRoutableFiles(ACTIONS_ROOT, next);
    },

    function (next: ErrorCallback): void {

        console.info(`File scan completed, found:\n
    ${filesEntityTable.length} Actions files
    ${actionFiles.length} Simple Actions files
    ${routerFiles.length} Advanced Router files\n
Building Simple Router Table:\n`);

        buildSimpleTable(ACTIONS_ROOT, next);
    },

    function (next: ErrorCallback): void {

        if (enabledDynamic) {

            console.info("\nBuilding Dynamic Router Table:\n");

            buildDynamicTable(ACTIONS_ROOT, next);

        } else {

            next();
        }
    },

    function (next: ErrorCallback): void {

        generateStaticTable(ACTIONS_ROOT, SRT_PATH, next);
    },

    function (next: ErrorCallback): void {

        generateDynamicTable(ACTIONS_ROOT, DRT_PATH, next);
    }

], function(err?: Error) {

    console.log("");

    if (err) {

        console.error(`[!] Error [${err.name}]: ${err.message}.`);

    } else {

        console.info("Router table has been successfully built.");
    }

});
