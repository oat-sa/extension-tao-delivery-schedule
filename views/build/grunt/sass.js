module.exports = function(grunt) { 

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoDeliverySchedule/views/';

    sass.taodeliveryschedule = { };
    sass.taodeliveryschedule.files = { };
    sass.taodeliveryschedule.files[root + 'css/taodeliveryschedule.css'] = root + 'scss/taodeliveryschedule.scss';

    watch.taodeliveryschedulesass = {
        files : [root + 'views/scss/**/*.scss'],
        tasks : ['sass:taodeliveryschedule', 'notify:taodeliveryschedulesass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taodeliveryschedulesass = {
        options: {
            title: 'Grunt SASS', 
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taodeliveryschedulesass', ['sass:taodeliveryschedule']);
};
