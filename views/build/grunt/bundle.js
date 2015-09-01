module.exports = function (grunt) {

    'use strict';

    var requirejs = grunt.config('requirejs') || {};
    var clean = grunt.config('clean') || {};
    var copy = grunt.config('copy') || {};

    var root = grunt.option('root');
    var libs = grunt.option('mainlibs');
    var ext = require(root + '/tao/views/build/tasks/helpers/extensions')(grunt, root);
    var out = 'output';
    /**
     * Remove bundled and bundling files
     */
    clean.taodeliveryschedulebundle = [out];

    /**
     * Compile tao files into a bundle 
     */
    requirejs.taodeliveryschedulebundle = {
        options: {
            baseUrl: '../js',
            dir: out,
            mainConfigFile: './config/requirejs.build.js',
            paths: {
                'taoDeliverySchedule': root + '/taoDeliverySchedule/views/js',
                'editDeliveryForm': 'empty:',
                'timeZoneList': 'empty:'
            },
            modules: [{
                name: 'taoDeliverySchedule/controller/routes',
                include: ext.getExtensionsControllers(['taoDeliverySchedule']),
                exclude: ['mathJax', 'mediaElement'].concat(libs)
            }]
        }
    };

    /**
     * copy the bundles to the right place
     */
    copy.taodeliveryschedulebundle = {
        files: [
            {src: [out + '/taoDeliverySchedule/controller/routes.js'], dest: root + '/taoDeliverySchedule/views/js/controllers.min.js'},
            {src: [out + '/taoDeliverySchedule/controller/routes.js.map'], dest: root + '/taoDeliverySchedule/views/js/controllers.min.js.map'}
        ]
    };

    grunt.config('clean', clean);
    grunt.config('requirejs', requirejs);
    grunt.config('copy', copy);

    // bundle task
    grunt.registerTask('taodeliveryschedulebundle', ['clean:taodeliveryschedulebundle', 'requirejs:taodeliveryschedulebundle', 'copy:taodeliveryschedulebundle']);
};
