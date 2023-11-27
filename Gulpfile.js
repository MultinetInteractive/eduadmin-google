const gulp = require("gulp");
const replace = require("gulp-replace");
const pinfo = require("./package.json");

/* Debug */

gulp.task("readme-version", function () {
    return gulp
        .src("src/readme.*")
        .pipe(replace("$PLUGINVERSION$", pinfo.version))
        .pipe(replace("$PLUGINATLEAST$", pinfo.config.eduadmin.requiresAtLeast))
        .pipe(replace("$PLUGINTESTEDTO$", pinfo.config.eduadmin.testedUpTo))
        .pipe(
            replace(
                "$PLUGINREQUIREDPHP$",
                pinfo.config.eduadmin.minimumPhpVersion
            )
        )
        .pipe(gulp.dest("./"));
});

gulp.task("plugin-version", function () {
    return gulp
        .src("src/eduadmin-analytics.php")
        .pipe(replace("$PLUGINVERSION$", pinfo.version))
        .pipe(replace("$PLUGINATLEAST$", pinfo.config.eduadmin.requiresAtLeast))
        .pipe(replace("$PLUGINTESTEDTO$", pinfo.config.eduadmin.testedUpTo))
        .pipe(gulp.dest("./"));
});


/* Deploy */


gulp.task("default", function () {
    gulp.watch("src/eduadmin-analytics.php", gulp.series("plugin-version"));
    gulp.watch("src/readme.*", gulp.series("readme-version"));
    gulp.watch(
        "package.json",
        gulp.series("readme-version", "plugin-version")
    );
});

gulp.task(
    "debug",
    gulp.series(
        "readme-version",
        "plugin-version"
    )
);

gulp.task(
    "deploy",
    gulp.series(
        "readme-version",
        "plugin-version"
    )
);
