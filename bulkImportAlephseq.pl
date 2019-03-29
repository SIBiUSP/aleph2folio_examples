
# perl version of bulkImportAlephseq.php

use Data::Walk;
use Data::GUID;
use Data::Dump qw(dump);
use Encode qw(encode_utf8);
use HTTP::Request();
use JSON::MaybeXS qw(encode_json);
use Data::Format::Pretty::JSON qw(format_pretty);
use LWP::UserAgent;
# use LWP::ConsoleLogger::Easy qw(debug_ua);
use Catmandu;
use Storable qw(dclone);
use strict;
use warnings;

my $ua = LWP::UserAgent->new;

# debug_ua($ua);
$ua->agent("MyApp/0.1");

my $tenant = 'diku';
my $password = 'admin';
my $username = 'diku_admin';

my $login_req = HTTP::Request->new(
 'POST',
 'http://172.31.1.52:9130/authn/login',
 [ 'X-Okapi-Tenant' => $tenant,
   'Content-Type' => 'application/json; charset=UTF-8',
   'Accept' => 'application/json' ],
 encode_utf8(encode_json({ 'username' => $username, 'password' => $password }))
);

my $login_res = $ua->request($login_req);

die "error: ".$login_res->status_line."\n" unless $login_res->is_success;

my $token = $login_res->header('X-Okapi-Token');

my $importer = Catmandu->importer('MARC', 'type' => 'ALEPHSEQ');
my $fixer = Catmandu->fixer($ARGV[0]);
my $mijfix = Catmandu->fixer('remove_field(_id)','marc_remove(FMT)','marc_in_json()');

$importer->each( sub {

  my $data = shift;

  my $rawd = dclone $data;

  $fixer->fix($data);

  my $instanceID = $$data{'id'};

  my $post_req = HTTP::Request->new(
    'POST',
    "http://172.31.1.52:9130/instance-storage/instances",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_json($data)
  );

  my $post_resp = $ua->request($post_req);
  die "FAILED loading " . $post_resp->status_line . ": " . $post_resp->content . "\n" unless $post_resp->is_success;

  $mijfix->fix($rawd);

  my $mijdata = encode_json($rawd);

  my $put_req = HTTP::Request->new(
    'PUT',
    "http://172.31.1.52:9130/instance-storage/instances/$instanceID/source-record/marc-json",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_utf8($mijdata)
  );

  my $put_resp = $ua->request($put_req);
  die "FAILED loading " . $put_resp->status_line . ": " . $put_resp->content . "\n" unless $put_resp->is_success;

  my $item = {};

  walk sub { 
    if(ref($_) eq 'HASH' && exists($_->{'Z30'})){

      $item->{'id'} = Data::GUID->new->as_string;
      $item->{'holdingsRecordId'} = '';

      my $counting = 0;
      foreach my $v ($_->{'Z30'}->{'subfields'}){
        $counting++;
        # if(ref($v) eq 'HASH' && exists($v->{'5'})){
        #   $item->{'barcode'} = $v->{'5'};
        #}
      }
      # $item->{'barcode'} = join( unshift(@legais,'][') ) unless exists($item->{'barcode'});

      $item->{'barcode'} = $counting unless exists($item->{'barcode'});

      $item->{'itemLevelCallNumber'} = $_->{'Z30'}->{'subfields'}[5]->{'3'};

      # ($item->{'barcode'}) = map { ( ref($_) eq 'HASH' && exists($_->{'5'}) ) ? $_->{'5'} : () } $_->{'Z30'}->{'subfields'};
      # ( $item->{'barcode'} ) =  $_->{'Z30'}->{'subfields'};

      print "\n\n\nolha a bib: " ,$_->{'Z30'}->{'subfields'}[3]->{'1'}, "\n";
      print " item json:[\n", encode_json($item), "\n]\n";
      print " leaf json:[\n", encode_json($_), "\n]\n";

      return ();

    };
   }, $rawd->{'fields'};

});

