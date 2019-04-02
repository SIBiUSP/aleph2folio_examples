
# perl version of bulkImportAlephseq.php
# sample usage:
# perl bulkImportAlephseq.pl fixesBias.txt < input/2records.seq ; ./deleteAll.sh
#

use Data::Walk;
use Data::GUID;
use Data::Dump qw(dump);
use Encode qw(encode_utf8);
use HTTP::Request();
use JSON::MaybeXS qw(encode_json);
use JSON::MaybeXS qw(decode_json);
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

my $okapiurl = 'http://172.31.1.52:9130';
my $tenant = 'diku';
my $password = 'admin';
my $username = 'diku_admin';

my $login_req = HTTP::Request->new(
 'POST',
 "$okapiurl/authn/login",
 [ 'X-Okapi-Tenant' => $tenant,
   'Content-Type' => 'application/json; charset=UTF-8',
   'Accept' => 'application/json' ],
 encode_utf8(encode_json({ 'username' => $username, 'password' => $password }))
);

my $login_res = $ua->request($login_req);

die "error: ".$login_res->status_line."\n" unless $login_res->is_success;

my $token = $login_res->header('X-Okapi-Token');


my $get_req = HTTP::Request->new(
  'GET',
  "$okapiurl/locations?limit=2000",
  ['X-Okapi-Tenant' => $tenant,
  'Content-Type' => 'application/json; charset=UTF-8',
  'Accept' => 'application/json',
  'X-Okapi-Token' => $token ]
);

my $get_resp = $ua->request($get_req);
die "FAILED loading " . $get_resp->status_line . ": " . $get_resp->content . "\n" unless $get_resp->is_success;

my $locations = decode_json($get_resp->content);

my $holdings = {};

my $importer = Catmandu->importer('MARC', 'type' => 'ALEPHSEQ');
my $fixer = Catmandu->fixer($ARGV[0]);
my $mijfix = Catmandu->fixer('remove_field(_id)','marc_remove(FMT)','marc_in_json()');

$importer->each( sub {

  my $data = shift;

  my $mijd = dclone $data;

  $fixer->fix($data);

  my $instanceID = $data->{'id'};

  my $post_req = HTTP::Request->new(
    'POST',
    "$okapiurl/instance-storage/instances",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_json($data)
  );

  my $post_resp = $ua->request($post_req);
  die "FAILED loading " . $post_resp->status_line . ": " . $post_resp->content . "\n" unless $post_resp->is_success;

  $mijfix->fix($mijd);

  my $put_req = HTTP::Request->new(
    'PUT',
    "$okapiurl/instance-storage/instances/$instanceID/source-record/marc-json",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_utf8(encode_json($mijd))
  );

  my $put_resp = $ua->request($put_req);
  die "FAILED loading " . $put_resp->status_line . ": " . $put_resp->content . "\n" unless $put_resp->is_success;

  foreach ( map {exists($_->{'Z30'}) ? $_->{'Z30'} : ()} @{$mijd->{'fields'}} ) {
    foreach ( @{$_->{'subfields'}} ){
      if( exists($_->{'1'}) && !exists($holdings->{$_->{'1'}.'-'.$instanceID}) ){
        my $bibcode = $_->{'1'};
        $holdings->{$bibcode.'-'.$instanceID}->{'id'} = Data::GUID->new->as_string;
        $holdings->{$bibcode.'-'.$instanceID}->{'instanceId'} = $instanceID;
        $holdings->{$bibcode.'-'.$instanceID}->{'permanentLocationId'} = (map { $_->{'code'} eq $bibcode ? $_->{'id'} : () } @{$locations->{'locations'}})[0];



      }
    }
    my $item = {};
    $item->{'id'} = Data::GUID->new->as_string;
    $item->{'materialTypeId'} = '1a54b431-2e4f-452d-9cae-9cee66c9a892';
    $item->{'permanentLoanTypeId'} = '2b94c631-fca9-4892-a730-03ee529ffe27';
    foreach ( @{$_->{'subfields'}} ){
      if(exists($_->{'1'})){
        $item->{'holdingsRecordId'} = $holdings->{$_->{'1'}.'-'.$instanceID}->{'id'};
      }
      if(exists($_->{'3'})){
        $item->{'itemLevelCallNumber'} = $_->{'3'};
      }
      if(exists($_->{'5'})){
        $item->{'barcode'} = $_->{'5'};
      }
    }
    print "item json:[\n", encode_json($item), "\n]\n";
    undef $item;
  }

});

print " holdings json:[\n", encode_json($holdings), "\n]\n";

# dump($locations);

