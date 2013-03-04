set     :application,           "Allegro"
set     :user,                  "www-data"
set     :deploy_to,             "/var/www/allegro.shoploapp.com"
set     :app_path,              "app"
set     :repository,            "git@github.com:grzegorz-lech/Allegro.git"
set     :scm,                   :git
set     :scm_verbose,           true
set     :model_manager,         "doctrine"
set     :use_sudo,              false
set     :keep_releases,         3
set     :use_composer,          true
set     :update_vendors,        true
set     :copy_vendors,          true
set     :update_assets_version, true
set     :dump_assetic_assets,   true
set     :shared_children,       [app_path + "/logs"]
set     :shared_files,          ["app/config/parameters.yml"]
set     :deploy_via,            :remote_cache
set     :writable_dirs,         ["app/cache", "app/logs"]
set     :webserver_user,        "apache"
role    :web,                   "allegro.shoploapp.com"
role    :app,                   "allegro.shoploapp.com"
role    :db,                    "allegro.shoploapp.com", :primary => true

# Configuration file
after "deploy:setup", "upload_parameters"
task :upload_parameters do
    origin_file = "app/config/parameters.yml"
    destination_file = shared_path + "/app/config/parameters.yml"

    try_sudo "mkdir -p #{File.dirname(destination_file)}"
    top.upload(origin_file, destination_file)
end

# Clean releases
after "deploy", "deploy:cleanup"

# Verbosity of messages
logger.level = Logger::IMPORTANT # MAX_LEVEL
