# config valid only for current version of Capistrano
lock '3.3.5'

set :application, 'ploufplouf'
set :repo_url, 'https://github.com/conradfr/ploufplouf.git'

# Default branch is :master
# ask :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }.call

# Default deploy_to directory is /var/www/my_app_name
set :deploy_to, "/var/www/#{fetch(:application)}"

# Default value for :scm is :git
# set :scm, :git

# Default value for :format is :pretty
# set :format, :pretty

# Default value for :log_level is :debug
# set :log_level, :debug

# Default value for :pty is false
# set :pty, true

# Default value for :linked_files is []
set :linked_files, fetch(:linked_files, []).push("app/config/#{fetch(:stage)}.yml")

# Default value for linked_dirs is []
set :linked_dirs, fetch(:linked_dirs, []).push('vendor', 'web/bower_components');

# Default value for default_env is {}
# set :default_env, { path: "/opt/ruby/bin:$PATH" }

# Default value for keep_releases is 5
set :keep_releases, 3

# For composer task
SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"

# File permissions
set :file_permissions_paths, ["logs", "cache"]
set :file_permissions_chmod_mode, "0774"

namespace :deploy do

  before "deploy:updated", "deploy:set_permissions:chmod"
  before "deploy:updated", "myproject:composer"

end

namespace :myproject do

  # run install composer or self-update if already installed
  task :composer do
    on roles(:app) do
      within shared_path do
        unless test "[", "-e", "composer.phar", "]"
          invoke 'composer:install_executable'
        else
          invoke 'composer:self_update'
        end
      end
    end
  end

end
