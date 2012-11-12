set     :application,           "Allegro"
set     :user,                  "root"
set     :deploy_to,             "/var/www/html/allegro/www"
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
set     :permission_method,     :acl
role    :web,                   "alpha.nexis.pl", "bravo.nexis.pl"
role    :app,                   "alpha.nexis.pl", "bravo.nexis.pl"
role    :db,                    "alpha.nexis.pl", :primary => true

# logger.level = Logger::MAX_LEVEL

before 'symfony:composer:install', 'composer:copy_vendors'
before 'symfony:composer:update', 'composer:copy_vendors'

namespace :composer do
    task :copy_vendors, :except => { :no_release => true } do
        capifony_pretty_print "--> Copy vendor file from previous release"

        run "vendorDir=#{current_path}/vendor; if [ -d $vendorDir ] || [ -h $vendorDir ]; then cp -a $vendorDir #{latest_release}/vendor; fi;"
        capifony_puts_ok
    end
end

task :upload_parameters do
    origin_file = "app/config/parameters.yml"
    destination_file = shared_path + "/app/config/parameters.yml"

    try_sudo "mkdir -p #{File.dirname(destination_file)}"
    top.upload(origin_file, destination_file)
end

after "deploy:setup", "upload_parameters"

after "symfony:cache:warmup", "deploy:set_permissions"
after "deploy:set_permissions", "permission:setowner"

namespace :permission do
    task :setowner do
        capifony_pretty_print "--> Change folder to owner #{webserver_user}"
        run "chown -R #{webserver_user}: #{latest_release}/"
        capifony_puts_ok
    end
end
