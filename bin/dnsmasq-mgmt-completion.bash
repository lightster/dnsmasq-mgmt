#!/bin/bash

_dnsmasq_mgmt_completions()
{
  # if the first argument is being typed or the second argument is being typed after "help"
  if [[ "${COMP_CWORD}" == "1" || ("${COMP_CWORD}" == "2" && "${COMP_WORDS[1]}" == "help") ]]; then
    local commands=(
      "help"
      "list"
      "address:add"
      "address:list"
      "address:remove"
      "address:update"
      "cache:clear"
      "config:export"
      "dnsmasq:install"
      "sudoers:install"
      "workspace:list"
      "workspace:switch"
    )

    # filter the available commands based on the text typed so far
    local filtered=($(compgen -W "${commands[*]}" -- "${COMP_WORDS[${COMP_CWORD}]}"))

    # if the word being autocompleted contains a colon,
    # strip off everything in the matches up to the colon
    # to play nice with Bash treating the colon as a word separator
    if [[ "${COMP_WORDS[${COMP_CWORD}]}" == *":"* ]]; then
      for command in "${filtered[@]}"; do
        COMPREPLY+=("${command/*:}")
      done
    else
      COMPREPLY=("${filtered[@]}")
    fi

    # append a space to the autocomplete if there is only one match
    if [ "${#COMPREPLY[@]}" == 1 ]; then
      COMPREPLY=("${COMPREPLY[0]} ")
    fi

    return
  fi
}

complete -o bashdefault -o default -o nospace -F _dnsmasq_mgmt_completions dnsmasq-mgmt
