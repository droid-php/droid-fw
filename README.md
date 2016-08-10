Droid plugin: fw
======================

For more information on Droid, please check out [droidphp.com](http://droidphp.com)

## Example usage

Here's an example droid.yml inventory configuration, documenting various use cases

```yml
groups:
    cluster:
        hosts:
            - app
            - lb

hosts:
    app:
        private_ip: 10.0.0.101
        public_ip: 188.188.188.101
        firewall_rules:
            -
              comment: allow access from a specific ip, and the private ip of the app host
              address: 83.161.143.92, app:private
              port: 22
              action: allow
            -
              comment: Use 'all' to express 0.0.0.0/0
              address: all
              port: 80
              
    lb:
        private_ip: 10.0.0.1
        public_ip: 188.188.188.1
        firewall_rules:
            - 
              comment: Allow all incoming http traffic
              address: all
              port: 80
              action: allow
            - 
              comment: Allow SSH for specified ip
              address: 192.168.0.1
              port: 22
              action: allow
            - 
              comment: Allow SSH from host "app" private IP
              address: app:private
              port: 22
              action: allow
            - 
              comment: Allow HTTP from host "app" public IP
              address: app:public
              port: 80
              action: allow
            - 
              comment: Allow SMTP from private IPs of hosts in the "cluster" group
              address: cluster:private
              port: 25
              action: allow
            - 
              comment: Deny one IP explicitly
              address: 10.0.0.99
              port: 25
              action: deny
```
