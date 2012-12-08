set     :application,           "Allegro"
set     :user,                  "www-data"
set     :deploy_to,             "/var/www/allegro.nexis.pl"
set     :app_path,              "app"
set     :repository,            "git@github.com:sgrodzicki/Allegro.git"
set     :scm,                   :git
set     :scm_verbose,           true
set     :model_manager,         "doctrine"
set     :use_sudo,              false
set     :keep_releases,         3
set     :use_composer,          true
set     :update_vendors,        true
set     :update_assets_version, true
set     :dump_assetic_assets,   true
set     :shared_children,       [app_path + "/logs", "vendor"]
set     :shared_files,          ["app/config/parameters.yml"]
set     :deploy_via,            :remote_cache
set     :writable_dirs,         ["app/cache", "app/logs"]
set     :webserver_user,        "apache"
role    :web,                   "vps.nexis.pl"
role    :app,                   "vps.nexis.pl"
role    :db,                    "vps.nexis.pl", :primary => true

# Configuration file
after "deploy:setup", "upload_parameters"
task :upload_parameters do
    origin_file = "app/config/parameters.yml"
    destination_file = shared_path + "/app/config/parameters.yml"

    try_sudo "mkdir -p #{File.dirname(destination_file)}"
    top.upload(origin_file, destination_file)
end

# Copy vendors from previous release
before 'symfony:composer:update', 'composer:copy_vendors'
namespace :composer do
    task :copy_vendors, :except => { :no_release => true } do
        capifony_pretty_print "--> Copy vendor file from previous release"

        run "vendorDir=#{current_path}/vendor; if [ -d $vendorDir ] || [ -h $vendorDir ]; then cp -a $vendorDir #{latest_release}/vendor; fi;"
        capifony_puts_ok
    end
end

# Verbosity of messages
logger.level = Logger::IMPORTANT # MAX_LEVEL
