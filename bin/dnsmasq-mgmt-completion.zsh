#compdef dnsmasq-mgmt

function _dnsmasq-mgmt {
  local curcontext context state state_descr line

  _dnsmasq-mgmt_commands 1

  case $line[1] in
    help)
      _dnsmasq-mgmt_commands 2
      ;;

    "address:remove"|"address:update")
      _dnsmasq-mgmt_address
      ;;
  esac
}

function _dnsmasq-mgmt_commands {
  local arg_num="$1"

  _arguments -C \
    "${arg_num}: :(
      help
      list
      address:add
      address:list
      address:remove
      address:update
      cache:clear
      config:export
      dnsmasq:install
      sudoers:install
      workspace:list
      workspace:switch
    )" \
    ":command:->command"
}

function __address_list {
  compadd $(dnsmasq-mgmt address:list | awk '{print $1}')
}


function _dnsmasq-mgmt_address {
  _arguments '2:feature:__address_list'
}

compdef _dnsmasq-mgmt dnsmasq-mgmt
